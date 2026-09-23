<?php

namespace Tests\Feature\Provider;

use App\Models\ProviderProfile;
use App\Models\ProviderWorkExperience;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderWorkExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_add_list_update_and_delete_a_work_experience(): void
    {
        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);

        $store = $this->actingAs($user)->postJson('/api/provider/work-experiences', [
            'role_title' => 'Electrician',
            'employer_name' => 'ABC Electrical Services',
            'started_on' => '2018-01-01',
            'ended_on' => '2021-06-30',
        ])->assertCreated();

        $id = $store->json('data.id');
        $this->assertFalse($store->json('data.is_current'));

        $this->actingAs($user)->getJson('/api/provider/work-experiences')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)
            ->patchJson("/api/provider/work-experiences/{$id}", [
                'role_title' => 'Senior Electrician',
                'started_on' => '2018-01-01',
                'ended_on' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.role_title', 'Senior Electrician')
            ->assertJsonPath('data.is_current', true);

        $this->actingAs($user)->deleteJson("/api/provider/work-experiences/{$id}")->assertOk();

        $this->actingAs($user)->getJson('/api/provider/work-experiences')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_ended_on_cannot_be_before_started_on(): void
    {
        $user = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson('/api/provider/work-experiences', [
            'role_title' => 'Electrician',
            'started_on' => '2020-01-01',
            'ended_on' => '2019-01-01',
        ])->assertStatus(422);
    }

    public function test_a_provider_cannot_edit_or_delete_another_providers_work_experience(): void
    {
        $owner = User::factory()->provider()->create();
        $ownerProfile = ProviderProfile::factory()->create(['user_id' => $owner->id]);
        $experience = ProviderWorkExperience::factory()->create(['provider_profile_id' => $ownerProfile->id]);

        $intruder = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $intruder->id]);

        $this->actingAs($intruder)
            ->patchJson("/api/provider/work-experiences/{$experience->id}", [
                'role_title' => 'Hijacked',
                'started_on' => '2020-01-01',
            ])
            ->assertStatus(403);

        $this->actingAs($intruder)
            ->deleteJson("/api/provider/work-experiences/{$experience->id}")
            ->assertStatus(403);
    }
}
