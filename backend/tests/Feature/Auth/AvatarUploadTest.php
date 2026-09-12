<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_image_uploads_successfully(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertOk();

        $path = $user->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->create('avatar.pdf', 10, 'application/pdf')])
            ->assertStatus(422);

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_uploading_a_new_avatar_deletes_the_previous_one(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->image('first.jpg')]);
        $firstPath = $user->fresh()->avatar_path;

        $this->actingAs($user)
            ->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->image('second.jpg')])
            ->assertOk();

        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($user->fresh()->avatar_path);
    }
}
