<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_verification_documents', function (Blueprint $table) {
            $table->boolean('replacement_requested')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('provider_verification_documents', function (Blueprint $table) {
            $table->dropColumn('replacement_requested');
        });
    }
};
