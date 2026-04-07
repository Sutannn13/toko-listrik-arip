<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAreaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return $next($request);
        }

        return redirect()
            ->route('home')
            ->with('error', 'Akses ditolak. Role user tidak dapat membuka area admin.');
    }
}
