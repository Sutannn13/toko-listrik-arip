<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_auth_token_store_returns_bearer_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-user@example.com',
            'password' => 'secret-pass-123',
        ]);

        $response = $this->postJson(route('api.auth.token.store'), [
            'email' => 'mobile-user@example.com',
            'password' => 'secret-pass-123',
            'device_name' => 'android-app',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Token API berhasil dibuat.');
        $response->assertJsonPath('data.token_type', 'Bearer');
        $this->assertNotEmpty($response->json('data.access_token'));

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'android-app',
        ]);
    }

    public function test_api_auth_token_store_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'mobile-user@example.com',
            'password' => 'secret-pass-123',
        ]);

        $response = $this->postJson(route('api.auth.token.store'), [
            'email' => 'mobile-user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Email atau password tidak valid.');
    }

    public function test_api_auth_token_destroy_revokes_current_token(): void
    {
        User::factory()->create([
            'email' => 'mobile-user@example.com',
            'password' => 'secret-pass-123',
        ]);

        $createTokenResponse = $this->postJson(route('api.auth.token.store'), [
            'email' => 'mobile-user@example.com',
            'password' => 'secret-pass-123',
            'device_name' => 'ios-app',
        ]);

        $createTokenResponse->assertCreated();

        $accessToken = (string) $createTokenResponse->json('data.access_token');
        $tokenId = (int) explode('|', $accessToken, 2)[0];

        $revokeResponse = $this
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->deleteJson(route('api.auth.token.destroy'));

        $revokeResponse->assertOk();
        $revokeResponse->assertJsonPath('message', 'Token API berhasil dicabut.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}
