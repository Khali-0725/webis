<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_filters_bookings_by_provider_category_and_date_with_sorting(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = ProviderProfile::factory()->verified()->create();
        $otherProvider = ProviderProfile::factory()->verified()->create();
        $category = ServiceCategory::factory()->create();
        $otherCategory = ServiceCategory::factory()->create();
        $service = Service::factory()->create(['provider_profile_id' => $provider->id, 'service_category_id' => $category->id]);
        $otherService = Service::factory()->create(['provider_profile_id' => $otherProvider->id, 'service_category_id' => $otherCategory->id]);
        $first = Booking::factory()->create(['provider_profile_id' => $provider->id, 'service_id' => $service->id, 'scheduled_date' => '2026-11-10']);
        $second = Booking::factory()->create(['provider_profile_id' => $provider->id, 'service_id' => $service->id, 'scheduled_date' => '2026-11-12']);
        Booking::factory()->create(['provider_profile_id' => $otherProvider->id, 'service_id' => $otherService->id, 'scheduled_date' => '2026-11-11']);

        $this->actingAs($admin)->getJson("/api/bookings?provider_id={$provider->id}&category_id={$category->id}&start_date=2026-11-10&end_date=2026-11-12&sort=oldest")
            ->assertOk()->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $second->id);

        $this->actingAs($admin)->getJson('/api/bookings?start_date=2026-11-13&end_date=2026-11-10')
            ->assertUnprocessable();
    }
}
