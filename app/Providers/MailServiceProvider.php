<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

use App\Models\Setting;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->resolving('mail.manager', function(){
            if (app()->runningInConsole() && !Schema::hasTable('settings')) {
                return;
            }
            $mail = Cache::rememberForever('config.mail.smtp', function () {
                return Setting::where('key', 'smtp')
                    ->where('group', 'mail')
                    ->first()?->value;
            });

            if ($mail && ($mail['is_active'] ?? false)) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp', [
                    'transport' => 'smtp',
                    'host' => $mail['host'],
                    'port' => $mail['port'],
                    'encryption' => $mail['encryption'],
                    'username' => $mail['username'],
                    'password' => isset($mail['password']) ? decrypt($mail['password']) : null,
                ]);

                Config::set('mail.from', [
                    'address' => $mail['from_address'],
                    'name' => $mail['from_name'],
                ]);
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
