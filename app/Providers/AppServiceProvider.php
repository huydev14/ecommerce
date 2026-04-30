<?php

namespace App\Providers;

use App\Listeners\ConfigureMailSetting;
use App\Observers\RoleObserver;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Role::observe(RoleObserver::class);
        Event::listen(MessageSending::class, ConfigureMailSetting::class);
    }
}
