<?php

namespace Tests\Feature\Public;

use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The suite's `array` cache store never serializes, so it cannot catch the
 * bug these tests guard against: `cache.serializable_classes` is `false`,
 * so any object that reaches a real store comes back as an incomplete
 * class on the next hit. These run on the `file` store to force a genuine
 * serialize/unserialize round trip and assert the *second* request works.
 */
class PublicListingCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'file']);
        Cache::store('file')->flush();
    }

    protected function tearDown(): void
    {
        Cache::store('file')->flush();

        parent::tearDown();
    }

    public function test_categories_survive_a_cache_hit(): void
    {
        ServiceCategory::factory()->create(['name' => 'Plumbing']);

        $first = $this->getJson('/api/service-categories')->assertOk()->json();
        $second = $this->getJson('/api/service-categories')->assertOk()->json();

        $this->assertSame($first, $second);
        $this->assertSame('Plumbing', $second['data'][0]['name']);
    }

    public function test_service_search_survives_a_cache_hit_with_identical_envelope(): void
    {
        $provider = ProviderProfile::factory()->verified()->create();
        Service::factory()->published()->create(['provider_profile_id' => $provider->id, 'title' => 'Pipe Repair']);

        $first = $this->getJson('/api/services?per_page=5')->assertOk()->json();
        $second = $this->getJson('/api/services?per_page=5')->assertOk()->json();

        $this->assertSame($first, $second);
        $this->assertSame('Pipe Repair', $second['data'][0]['title']);
        $this->assertSame(['page', 'per_page', 'total', 'last_page', 'from', 'to'], array_keys($second['meta']));
        $this->assertArrayHasKey('category', $second['data'][0]);
        $this->assertArrayHasKey('provider', $second['data'][0]);
    }
}
