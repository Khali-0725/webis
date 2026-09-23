<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Providers can optionally record a birthdate and choose whether their
 * computed age is shown on their public profile. `show_age_publicly`
 * defaults to false - opt-in, never opt-out - and the public resource must
 * only ever expose the computed age, never `birthdate` itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('experience_years');
            $table->boolean('show_age_publicly')->default(false)->after('birthdate');
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['birthdate', 'show_age_publicly']);
        });
    }
};
