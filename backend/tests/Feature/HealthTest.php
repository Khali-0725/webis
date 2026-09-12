<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_the_api_and_database_are_up(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.database', 'up')
            ->assertJsonStructure([
                'success',
                'data' => ['application', 'status', 'database', 'environment', 'time'],
            ]);
    }

    public function test_unknown_api_routes_return_the_failure_envelope_not_html(): void
    {
        $this->getJson('/api/this-does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_error_responses_do_not_leak_debug_details_when_debug_is_off(): void
    {
        config()->set('app.debug', false);

        $response = $this->getJson('/api/this-does-not-exist');

        $response->assertNotFound();
        $this->assertArrayNotHasKey('debug', $response->json());
    }
}
