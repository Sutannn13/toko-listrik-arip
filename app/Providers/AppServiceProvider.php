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
        RateLimiter::for('password-reset-link', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email'));
            $linkPerMinute = max(1, (int) config('auth.password_reset_rate_limits.link_per_minute', 5));

            return Limit::perMinute($linkPerMinute)->by($request->ip() . '|' . $email);
        });

        RateLimiter::for('password-reset-attempt', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email'));
            $attemptPerMinute = max(1, (int) config('auth.password_reset_rate_limits.attempt_per_minute', 8));

            return Limit::perMinute($attemptPerMinute)->by($request->ip() . '|' . $email);
        });
    }
}
