<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->resolving(Factory::class, function () {
            if (app()->runningInConsole() && !Schema::hasTable('settings')) {
                return;
            }
            try {
                $this->loadGoogleConfig();
                $this->loadMicrosoftConfig();
            } catch (\Exception $e) {
                Log::error("Lỗi khi nạp cấu hình OAuth động: " . $e->getMessage());
            }
        });
    }

    /**
     * Load google configs
     */
    private function loadGoogleConfig()
    {
        $config = Cache::rememberForever('config.oauth.google', function () {
            return Setting::where('group', 'oauth')->where('key', 'google')->first();
        });

        if (!$config || empty($config->value)) {
            return false;
        }

        $settings = $config->value;

        if (empty($settings['is_active'])) {
            return false;
        }

        Config::set('services.google', [
            'client_id' => $settings['client_id'] ?? null,
            'client_secret' => !empty($settings['client_secret']) ? decrypt($settings['client_secret']) : null,
            'redirect' => $settings['redirect_uri'] ?? null,
        ]);

        return true;
    }

    /**
     * Load microsoft configs
     */
    private function loadMicrosoftConfig()
    {
        $config = Cache::rememberForever('config.oauth.microsoft', function () {
            return Setting::where('group', 'oauth')->where('key', 'microsoft')->first();
        });

        if (!$config || empty($config->value)) {
            return false;
        }

        $settings = $config->value;

        if (empty($settings['is_active'])) {
            return false;
        }

        Config::set('services.microsoft', [
            'client_id' => $settings['client_id'] ?? null,
            'client_secret' => !empty($settings['client_secret']) ? decrypt($settings['client_secret']) : null,
            'redirect' => $settings['redirect_uri'] ?? null,
            'tenant' => $settings['tenant'] ?? 'common',
        ]);

        return true;
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
