<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `GET /api/files/avatar/{user}` is the one deliberately-public file route
 * in the app (see backend/BUILD-LOG.md's Phase 7 log) - every other file route
 * is policy-gated. Worth its own test precisely because it is the exception.
 */
class FilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_is_served_without_authentication(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'local');
        $user->forceFill(['avatar_path' => $path])->save();

        $this->get("/api/files/avatar/{$user->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_a_user_with_no_avatar_returns_404(): void
    {
        $user = User::factory()->create();

        $this->get("/api/files/avatar/{$user->id}")->assertStatus(404);
    }
}
