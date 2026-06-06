<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Department;
use App\Models\OAuthAccount;
use App\Models\Order;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\Team;
use App\Models\Warehouse;
use App\Observers\BannerObserver;
use App\Observers\CartObserver;
use App\Observers\CustomerAddressObserver;
use App\Observers\CustomerObserver;
use App\Observers\DepartmentObserver;
use App\Observers\OAuthAccountObserver;
use App\Observers\OrderObserver;
use App\Observers\PositionObserver;
use App\Observers\RoleObserver;
use App\Observers\SettingObserver;
use App\Observers\StockObserver;
use App\Observers\TeamObserver;
use App\Observers\WarehouseObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(MailServiceProvider::class);
        $this->app->register(OAuthServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        Event::listen(SocialiteWasCalled::class, [MicrosoftExtendSocialite::class, 'handle']);

        Banner::observe(BannerObserver::class);
        Cart::observe(CartObserver::class);
        Customer::observe(CustomerObserver::class);
        CustomerAddress::observe(CustomerAddressObserver::class);
        Department::observe(DepartmentObserver::class);
        OAuthAccount::observe(OAuthAccountObserver::class);
        Order::observe(OrderObserver::class);
        Position::observe(PositionObserver::class);
        Role::observe(RoleObserver::class);
        Setting::observe(SettingObserver::class);
        Stock::observe(StockObserver::class);
        Team::observe(TeamObserver::class);
        Warehouse::observe(WarehouseObserver::class);
    }
}
