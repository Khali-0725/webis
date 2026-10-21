<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            // Nullable preserves the audit history of proofs uploaded before
            // this revision. New submissions must supply both values.
            $table->decimal('declared_amount', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_proofs', fn (Blueprint $table) => $table->dropColumn(['declared_amount', 'paid_at']));
    }
};
