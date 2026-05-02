<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_google_user_can_register_without_password(): void
    {
        $this->mockGoogleUser(
            id: 'google-new-123',
            email: 'new-google@example.com',
            name: 'New Google User',
            verified: true,
        );

        $response = $this->get(route('auth.google.callback', ['code' => 'code', 'state' => 'state']));

        $user = User::query()->where('email', 'new-google@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
        $response->assertSessionHas('success');

        $this->assertSame('google-new-123', $user->google_id);
        $this->assertSame('google', $user->provider);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasAnyRole(['admin', 'super-admin']));
    }

    public function test_verified_google_login_links_existing_local_customer_by_email(): void
    {
        Role::findOrCreate('user', 'web');

        $existingPassword = Hash::make('password');
        $user = User::factory()->create([
            'name' => 'Local User',
            'email' => 'local-user@example.com',
            'google_id' => null,
            'provider' => 'local',
            'password' => $existingPassword,
            'email_verified_at' => null,
        ]);
        $user->assignRole('user');

        $this->mockGoogleUser(
            id: 'google-existing-456',
            email: 'local-user@example.com',
            name: 'Google Name',
            verified: true,
        );

        $response = $this->get(route('auth.google.callback', ['code' => 'code', 'state' => 'state']));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home', absolute: false));
        $response->assertSessionHas('success', 'Akun Google berhasil ditautkan ke akun Anda.');

        $user->refresh();

        $this->assertSame('google-existing-456', $user->google_id);
        $this->assertSame('local', $user->provider);
        $this->assertSame('Local User', $user->name);
        $this->assertSame($existingPassword, $user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasAnyRole(['admin', 'super-admin']));
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->mockGoogleUser(
            id: 'google-unverified-789',
            email: 'unverified@example.com',
            name: 'Unverified User',
            verified: false,
        );

        $response = $this->get(route('auth.google.callback', ['code' => 'code', 'state' => 'state']));

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
    }

    private function mockGoogleUser(string $id, string $email, string $name, bool $verified): void
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
        $googleUser->shouldReceive('getRaw')->andReturn(['email_verified' => $verified]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
