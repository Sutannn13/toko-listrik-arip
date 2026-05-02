<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
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
            Log::error('Google OAuth callback failed', [
                'exception'      => $e::class,
                'message'        => $e->getMessage(),
                'code'           => $e->getCode(),
                'has_oauth_code' => $request->filled('code'),
                'has_state'      => $request->filled('state'),
                'ip'             => $request->ip(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Gagal login dengan Google. Silakan coba lagi.']);
        }

        // Only trust Google login when Google confirms ownership of the email.
        $emailVerified = filter_var(
            $googleUser->getRaw()['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

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

        // Redirect directly so flash notices survive the post-login response.
        $redirect = $user->hasAnyRole(['super-admin', 'admin'])
            ? redirect()->route('admin.dashboard')
            : redirect()->route('home');

        if (! $user->hasLocalPassword()) {
            $redirect->with(
                'success',
                'Akun Google Anda sudah terverifikasi. Untuk keamanan tambahan, silakan pasang password cadangan di halaman profil.',
            );
        } elseif (session('google_account_linked')) {
            $redirect->with('success', 'Akun Google berhasil ditautkan ke akun Anda.');
        }

        return $redirect;
    }

    /**
     * Find an existing user or create a new one from Google profile data.
     *
     * Account linking strategy:
     * 1. google_id match -> return existing user (returning Google user)
     * 2. Email match, no google_id -> link only after Google verifies the email
     * 3. No match -> create new verified user with Google data
     *
     * SECURITY: We never auto-assign admin roles to Google users.
     *           We never overwrite existing passwords or names.
     *           We never overwrite existing local passwords or roles.
     */
    private function findOrCreateUser(SocialiteUser $googleUser): ?User
    {
        $googleId = $googleUser->getId();
        $rawEmail = $googleUser->getEmail();
        $rawName  = $googleUser->getName();
        $avatar   = $googleUser->getAvatar();

        $googleId = is_string($googleId) ? trim($googleId) : '';
        $rawEmail = is_string($rawEmail) ? trim($rawEmail) : '';
        $rawName  = is_string($rawName) ? trim($rawName) : '';

        if ($googleId === '' || $rawEmail === '') {
            Log::warning('Google OAuth: missing required profile data', [
                'has_google_id' => $googleId !== '',
                'has_email'     => $rawEmail !== '',
            ]);

            return null;
        }

        $email = Str::lower($rawEmail);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Google OAuth: invalid email format from provider', [
                'email' => $rawEmail,
            ]);

            return null;
        }

        $name = $rawName !== '' ? $rawName : Str::before($email, '@');

        if ($name === '') {
            $name = 'Pengguna Google';
        }

        // Strategy 1: Find by google_id (returning user)
        $user = User::where('google_id', $googleId)->first();

        if ($user !== null) {
            // Update avatar in case it changed
            if ($avatar && $user->avatar !== $avatar) {
                $user->update(['avatar' => $avatar]);
            }

            return $user;
        }

        // Strategy 2: Link an existing local account after verified Google email proof
        // Google email_verified is required before this method is called.
        $existingUser = User::where('email', $email)->first();

        if ($existingUser !== null) {
            $existingUser->forceFill([
                'google_id'         => $googleId,
                'avatar'            => $avatar ?: $existingUser->avatar,
                'email_verified_at' => $existingUser->email_verified_at ?? now(),
            ])->save();

            Log::info('Google OAuth: linked verified Google account to existing user', [
                'user_id'   => $existingUser->id,
                'email'     => $email,
                'google_id' => $googleId,
            ]);

            // Mark this request so the callback can show a friendly linked-account notice.
            session()->flash('google_account_linked', true);

            return $existingUser;
        }

        // Strategy 3: Create new user
        try {
            $user = DB::transaction(function () use ($name, $email, $googleId, $avatar): User {
                $user = User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'google_id'         => $googleId,
                    'avatar'            => $avatar,
                    'provider'          => 'google',
                    'password'          => null,
                    'email_verified_at' => now(),
                ]);

                // Assign default customer role only. Never assign admin here.
                Role::findOrCreate('user', 'web');
                $user->assignRole('user');

                return $user;
            });

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
