<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('welcome_message', 500)->nullable();
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_automated')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('messages', fn (Blueprint $table) => $table->dropColumn('is_automated'));
        Schema::table('provider_profiles', fn (Blueprint $table) => $table->dropColumn('welcome_message'));
    }
};
