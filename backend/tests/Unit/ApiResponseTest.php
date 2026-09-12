<?php

namespace Tests\Unit;

use App\Support\Api\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    public function test_success_envelope_shape(): void
    {
        $payload = ApiResponse::ok(['id' => 1], 'Fetched.')->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(['id' => 1], $payload['data']);
        $this->assertSame('Fetched.', $payload['message']);
        $this->assertArrayNotHasKey('errors', $payload);
    }

    public function test_meta_is_omitted_when_empty(): void
    {
        $payload = ApiResponse::ok(['id' => 1])->getData(true);

        $this->assertArrayNotHasKey('meta', $payload);
    }

    public function test_failure_envelope_shape(): void
    {
        $response = ApiResponse::error('Unable to process booking.', [
            'scheduled_start_time' => ['That time slot is no longer available.'],
        ], 409);

        $payload = $response->getData(true);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Unable to process booking.', $payload['message']);
        $this->assertSame(
            ['That time slot is no longer available.'],
            $payload['errors']['scheduled_start_time']
        );
    }

    public function test_errors_serialise_as_an_object_not_an_array(): void
    {
        $json = ApiResponse::error('Nope.')->getContent();

        $this->assertStringContainsString('"errors":{}', $json);
    }

    public function test_created_uses_201(): void
    {
        $this->assertSame(201, ApiResponse::created(['id' => 9])->getStatusCode());
    }
}
