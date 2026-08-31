<?php

namespace Tests\Feature\Health;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_liveness_endpoint_is_available_and_has_request_id(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertHeader('X-Request-ID');
    }

    public function test_readiness_endpoint_checks_database_without_leaking_details(): void
    {
        $response = $this->get('/health/ready');

        $response->assertOk()
            ->assertExactJson(['status' => 'ready']);

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
        );
    }

    public function test_valid_caller_request_id_is_propagated(): void
    {
        $requestId = 'client-request-12345678';

        $this->withHeader('X-Request-ID', $requestId)
            ->get('/up')
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId);
    }

    public function test_unsafe_request_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-ID', "bad\nvalue")
            ->get('/up');

        $response->assertOk();
        $this->assertNotSame("bad\nvalue", $response->headers->get('X-Request-ID'));
    }
}
