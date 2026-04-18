<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_RESET_LINK_MESSAGE = 'Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.';

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', self::GENERIC_RESET_LINK_MESSAGE);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_response_is_generic_for_unknown_email(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'unknown@example.com']);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', self::GENERIC_RESET_LINK_MESSAGE);
    }

    public function test_forgot_password_response_remains_generic_when_mail_transport_fails(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new RuntimeException('Simulated SMTP transport failure'));

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'known-user@example.com',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', self::GENERIC_RESET_LINK_MESSAGE);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/' . $notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('home'));

            $this->assertAuthenticatedAs($user);

            return true;
        });
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_forgot_password_endpoint_is_rate_limited_when_abused(): void
    {
        $payload = ['email' => 'abuse-forgot@example.com'];
        $linkPerMinute = (int) config('auth.password_reset_rate_limits.link_per_minute', 5);

        for ($attempt = 0; $attempt < $linkPerMinute; $attempt++) {
            $this->post('/forgot-password', $payload);
        }

        $response = $this->post('/forgot-password', $payload);

        $response->assertStatus(429);
    }

    public function test_reset_password_endpoint_is_rate_limited_when_abused(): void
    {
        $payload = [
            'token' => 'invalid-token',
            'email' => 'abuse-reset@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $attemptPerMinute = (int) config('auth.password_reset_rate_limits.attempt_per_minute', 8);

        for ($attempt = 0; $attempt < $attemptPerMinute; $attempt++) {
            $this->post('/reset-password', $payload);
        }

        $response = $this->post('/reset-password', $payload);

        $response->assertStatus(429);
    }
}
