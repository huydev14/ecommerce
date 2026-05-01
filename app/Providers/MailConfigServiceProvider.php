<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->resolving('mail.manager', function(){
            $mail = Cache::rememberForever('config.mail.smtp', function () {
                return Setting::where('key', 'smtp')
                    ->where('group', 'mail')
                    ->first()?->value;
            });

            if ($mail && ($mail['is_active'] ?? false)) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp', [
                    'transport' => 'smtp',
                    'host' => $mail['host'] ?? env('MAIL_HOST'),
                    'port' => $mail['port'] ?? env('MAIL_PORT'),
                    'encryption' => $mail['encryption'] ?? env('MAIL_ENCRYPTION'),
                    'username' => $mail['username'] ?? env('MAIL_USERNAME'),
                    'password' => isset($mail['password']) ? decrypt($mail['password']) : decrypt(env('MAIL_PASSWORD')),
                ]);

                Config::set('mail.from', [
                    'address' => $mail['from_address'] ?? env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                    'name' => $mail['from_name'] ?? env('MAIL_FROM_NAME', 'System'),
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
