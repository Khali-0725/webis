<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/me/profile', [
                'first_name' => 'New',
                'last_name' => 'Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'New');
    }
}
