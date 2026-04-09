<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $intendedUrl = $request->session()->pull('url.intended');

        if ($user && $user->hasAnyRole(['super-admin', 'admin'])) {
            if ($intendedUrl) {
                return redirect()->to($intendedUrl);
            }

            return redirect()->route('admin.dashboard');
        }

        if ($intendedUrl && !Str::contains($intendedUrl, '/admin')) {
            return redirect()->to($intendedUrl);
        }

        return redirect()->route('home');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'Anda berhasil logout.');
    }
}
