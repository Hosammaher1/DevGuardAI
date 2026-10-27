<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_healthy_status(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'devguard-api',
            ])
            ->assertJsonStructure([
                'status',
                'service',
                'timestamp',
            ]);
    }
}
