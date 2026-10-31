<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Separate request limits per feature. Plain "throttle:N,1" shares ONE counter per
        // visitor across every route that uses it, so page-view beacons and cart clicks used
        // up the checkout allowance and checkout answered "429 Too Many Requests".
        foreach (['beacon' => 120, 'cart' => 60, 'checkout' => 10, 'orders' => 30, 'webhook' => 120, 'account' => 20, 'stats' => 30] as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $r) => Limit::perMinute($perMinute)->by($name.'|'.$r->ip()));
        }
    }
}
