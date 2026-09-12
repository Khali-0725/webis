<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Runtime configuration the admin can change (platform maintenance mode,
 * moderation thresholds, booking lead times). Values are JSON so scalars
 * and structures share one shape; `group` powers the settings UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            $table->string('key', 100)->unique();
            $table->json('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('description', 255)->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
