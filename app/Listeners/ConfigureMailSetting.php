<?php

namespace App\Listeners;

use App\Models\Setting;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ConfigureMailSetting
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSending $event): void
    {
        $mail = Cache::rememberForever('config.mail.smtp', function () {
            return Setting::where('key', 'smtp')
                ->where('group', 'mail')
                ->first()?->value;
        });

        if ($mail && ($mail['is_active'] ?? false)) {
            Config::set('mail.mailers.smtp.host', $mail['host']);
            Config::set('mail.mailers.smtp.port', $mail['port']);
            Config::set('mail.mailers.smtp.username', $mail['username']);

            if (!empty($mail['password'])) {
                Config::set('mail.mailers.smtp.password', decrypt($mail['password']));
            }
            if (!empty($mail['encryption'])) {
                Config::set('mail.mailers.smtp.encryption', $mail['encryption']);
            }
            
            Config::set('mail.from.address', $mail['from_address'] ?? $mail['username']);
            Config::set('mail.from.name', $mail['from_name'] ?? config('app.name'));
        }
    }
}
