<?php

namespace Tests\Feature\Public;

use App\Models\Barangay;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_barangays_and_categories_are_publicly_listable(): void
    {
        Barangay::factory()->create(['name' => 'Amaya 1']);
        Barangay::factory()->inactive()->create(['name' => 'Hidden Barangay']);
        ServiceCategory::factory()->create(['name' => 'Plumbing']);
        ServiceCategory::factory()->inactive()->create(['name' => 'Hidden Category']);

        $this->getJson('/api/barangays')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/service-categories')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_only_returns_published_services_from_verified_providers(): void
    {
        $verified = ProviderProfile::factory()->verified()->create();
        Service::factory()->published()->create(['provider_profile_id' => $verified->id, 'title' => 'Pipe Repair']);

        $unverified = ProviderProfile::factory()->create();
        Service::factory()->published()->create(['provider_profile_id' => $unverified->id, 'title' => 'Pipe Repair Unverified']);

        $unpublished = ProviderProfile::factory()->verified()->create();
        Service::factory()->create(['provider_profile_id' => $unpublished->id, 'title' => 'Pipe Repair Draft']);

        $response = $this->getJson('/api/services')->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Pipe Repair'));
        $this->assertFalse($titles->contains('Pipe Repair Unverified'));
        $this->assertFalse($titles->contains('Pipe Repair Draft'));
    }

    public function test_search_filters_by_keyword_category_and_barangay(): void
    {
        $barangay = Barangay::factory()->create();
        $category = ServiceCategory::factory()->create(['slug' => 'plumbing']);
        $provider = ProviderProfile::factory()->verified()->create(['base_barangay_id' => $barangay->id]);

        Service::factory()->published()->create([
            'provider_profile_id' => $provider->id,
            'service_category_id' => $category->id,
            'title' => 'Emergency Plumbing',
        ]);

        $other = ProviderProfile::factory()->verified()->create();
        Service::factory()->published()->create(['provider_profile_id' => $other->id, 'title' => 'Haircut']);

        $this->getJson('/api/services?q=Plumbing')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/services?category={$category->slug}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/services?barangay_id={$barangay->id}")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_service_detail_hides_unpublished_or_unverified_services(): void
    {
        $unverified = ProviderProfile::factory()->create();
        $service = Service::factory()->published()->create(['provider_profile_id' => $unverified->id]);

        $this->getJson("/api/services/{$service->id}")->assertNotFound();
    }

    public function test_provider_profile_is_hidden_when_not_verified(): void
    {
        $provider = ProviderProfile::factory()->create();

        $this->getJson("/api/providers/{$provider->id}")->assertNotFound();
    }
}
