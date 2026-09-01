<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_only_added_to_secure_requests_when_enabled(): void
    {
        config()->set('security.headers.hsts.enabled', true);

        $this->get('/')
            ->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_csp_can_be_started_in_report_only_mode(): void
    {
        config()->set('security.headers.csp_report_only', "default-src 'self'");

        $this->get('/')
            ->assertHeader('Content-Security-Policy-Report-Only', "default-src 'self'")
            ->assertHeaderMissing('Content-Security-Policy');
    }
}
