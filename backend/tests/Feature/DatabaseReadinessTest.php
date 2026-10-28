<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class DatabaseReadinessTest extends TestCase
{
    public function test_database_readiness_reports_live_connection(): void
    {
        $this->getJson('/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'ready', 'database' => 'connected']);
    }

    public function test_database_readiness_fails_without_exposing_connection_details(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('secret connection detail'));

        $this->getJson('/ready')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable', 'database' => 'disconnected']);
    }
}
