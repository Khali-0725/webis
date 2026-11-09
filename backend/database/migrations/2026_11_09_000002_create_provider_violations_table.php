<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained('provider_profiles')->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('penalty_points');
            $table->string('reason', 255);
            $table->timestamps();
            $table->index(['provider_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_violations');
    }
};
