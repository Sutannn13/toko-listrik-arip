<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiSecurityHardeningTest extends TestCase
{
    public function test_api_response_includes_security_headers(): void
    {
        config()->set('security.headers_enabled', true);
        config()->set('security.hsts_max_age', 31536000);

        $response = $this
            ->withServerVariables([
                'HTTPS' => 'on',
                'SERVER_PORT' => 443,
            ])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->postJson(route('api.auth.token.store'), []);

        $response->assertStatus(422);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_cors_rejects_unknown_origin(): void
    {
        $response = $this
            ->withHeaders([
                'Origin' => 'https://evil.example',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'Content-Type',
            ])
            ->options('/api/auth/token');

        $this->assertTrue(in_array($response->getStatusCode(), [200, 204], true));
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_cors_allows_whitelisted_origin(): void
    {
        $response = $this
            ->withHeaders([
                'Origin' => 'http://localhost',
                'Access-Control-Request-Method' => 'POST',
                'Access-Control-Request-Headers' => 'Content-Type',
            ])
            ->options('/api/auth/token');

        $this->assertTrue(in_array($response->getStatusCode(), [200, 204], true));
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost');
    }
}
