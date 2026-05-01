<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     *
     * Socialite handles the state parameter internally to prevent CSRF.
     * Scopes are limited to openid, email, and profile (minimal data).
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    /**
     * Handle the callback from Google OAuth.
     *
     * Flow:
     * 1. Exchange authorization code for user info (server-side)
     * 2. Validate email_verified claim
     * 3. Find existing user by google_id OR link by email
     * 4. Create new user if neither exists
     * 5. Check suspension status
     * 6. Login and redirect based on role
     */
    public function callback(Request $request): RedirectResponse
    {
        // Handle user cancellation (Google redirects with error param)
        if ($request->has('error')) {
            Log::info('Google OAuth cancelled by user', [
                'error' => $request->query('error'),
                'ip'    => $request->ip(),
            ]);

            return redirect()
                ->route('login')
                ->with('status', 'Login dengan Google dibatalkan.');
        }

        try {
            /**
             * Socialite::stateless() is NOT used here intentionally.
             * We keep state verification (CSRF protection) active.
             * The state token is validated automatically by Socialite.
             */
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::warning('Google OAuth callback failed', [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Gagal login dengan Google. Silakan coba lagi.']);
        }

        // Validate that Google has verified this email address
        $emailVerified = $googleUser->getRaw()['email_verified'] ?? false;

        if (!$emailVerified) {
            Log::warning('Google OAuth: unverified email attempt', [
                'email' => $googleUser->getEmail(),
                'ip'    => $request->ip(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Email Google Anda belum diverifikasi. Silakan verifikasi email di Google terlebih dahulu.']);
        }

        $user = $this->findOrCreateUser($googleUser);

        if ($user === null) {
            // Check if this was an email conflict (email already registered locally)
            if (session('google_email_conflict')) {
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Email ini sudah terdaftar dengan akun lokal. Silakan login menggunakan email dan password Anda terlebih dahulu.']);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Terjadi kesalahan saat memproses akun. Silakan coba lagi.']);
        }

        // Check suspension status before granting access
        if ($user->isSuspended()) {
            Log::info('Google OAuth: suspended user attempted login', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $request->ip(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun Anda telah disuspend. Silakan hubungi admin untuk info lebih lanjut.']);
        }

        // Login with session regeneration for security
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        Log::info('Google OAuth: successful login', [
            'user_id'  => $user->id,
            'email'    => $user->email,
            'provider' => $user->provider,
            'ip'       => $request->ip(),
        ]);

        // Redirect to /dashboard — that route already handles role-based redirect
        // (admin → admin.dashboard, user → home)
        return redirect()->route('dashboard');
    }

    /**
     * Find an existing user or create a new one from Google profile data.
     *
     * Account linking strategy:
     * 1. google_id match → return existing user (returning Google user)
     * 2. Email match, no google_id → REJECT, user must login manually first
     * 3. No match → create new user with Google data
     *
     * SECURITY: We never auto-assign admin roles to Google users.
     *           We never overwrite existing passwords or names.
     *           We never auto-link Google to existing local accounts.
     */
    private function findOrCreateUser(
        \Laravel\Socialite\Contracts\User $googleUser
    ): ?User {
        $googleId = $googleUser->getId();
        $email    = Str::lower($googleUser->getEmail());
        $name     = $googleUser->getName();
        $avatar   = $googleUser->getAvatar();

        // Strategy 1: Find by google_id (returning user)
        $user = User::where('google_id', $googleId)->first();

        if ($user !== null) {
            // Update avatar in case it changed
            if ($avatar && $user->avatar !== $avatar) {
                $user->update(['avatar' => $avatar]);
            }

            return $user;
        }

        // Strategy 2: Email already exists but no google_id linked
        // Do NOT auto-link — user must login manually first to prove ownership
        $existingUser = User::where('email', $email)->first();

        if ($existingUser !== null) {
            Log::info('Google OAuth: email already registered locally, rejected auto-link', [
                'user_id' => $existingUser->id,
                'email'   => $email,
            ]);

            // Return null — caller will show a specific error
            // We set a session flash so the caller can show the right message
            session()->flash('google_email_conflict', true);

            return null;
        }

        // Strategy 3: Create new user
        try {
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'google_id'         => $googleId,
                'avatar'            => $avatar,
                'provider'          => 'google',
                'password'          => null,
                'email_verified_at' => now(),
            ]);

            // Assign default role — NEVER admin
            Role::findOrCreate('user', 'web');
            $user->assignRole('user');

            Log::info('Google OAuth: created new user', [
                'user_id'   => $user->id,
                'email'     => $email,
                'google_id' => $googleId,
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error('Google OAuth: failed to create user', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
