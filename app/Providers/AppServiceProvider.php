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
        foreach (['beacon' => 120, 'cart' => 60, 'checkout' => 10, 'orders' => 30, 'webhook' => 120, 'account' => 20, 'stats' => 30, 'forum' => 20, 'chat' => 15, 'quotation' => 90] as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $r) => Limit::perMinute($perMinute)->by($name.'|'.$r->ip()));
        }

        // Course answer checks: counted per signed-in member, not per internet address. Many students in the
        // Philippines share one address (mobile data / CGNAT), and a per-address limit blocked them.
        RateLimiter::for('course-check', function (Request $r) {
            $id = \App\Support\Members::current()?->id ?? \App\Support\Forum::staff()?->id;

            return $id ? Limit::perMinute(20)->by('course-check|m'.$id) : Limit::perMinute(120)->by('course-check|'.$r->ip());
        });

        // The mailbox password saved in Admin → Subscribers takes the place of MAIL_PASSWORD.
        try {
            \App\Support\MailPassword::apply();
            \App\Support\GmailSender::apply();
        } catch (\Throwable $e) {
        }
    }
}
