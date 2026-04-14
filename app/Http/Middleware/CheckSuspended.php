<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuspended
{
    public function handle(Request $request, Closure $next)
    {
        $authenticatedUser = Auth::user();

        if ($authenticatedUser instanceof User && $authenticatedUser->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah disuspend. Silakan hubungi admin untuk info lebih lanjut.',
            ]);
        }

        return $next($request);
    }
}
