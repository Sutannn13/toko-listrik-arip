<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        // Jika maintenance mode aktif, dan URL BUKAN admin atau login
        if (Setting::get('maintenance_mode') === true) {
            if (! $request->is('admin/*') && ! $request->is('admin') && ! $request->is('login') && ! $request->is('register') && ! $request->is('forgot-password') && ! $request->is('reset-password/*')) {
                return response()->view('errors.maintenance', [], 503);
            }
        }

        return $next($request);
    }
}
