<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class RateLimitTest extends TestCase
{
    public function test_readiness_endpoint_is_rate_limited(): void
    {
        config()->set('security.rate_limits.health_per_minute', 1);

        $this->get('/health/ready')->assertOk();
        $this->get('/health/ready')->assertStatus(429);
    }
}
