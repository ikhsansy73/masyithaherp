<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Doc 02 §3 defines payments.amount as a signed bigint: the void
     * flow records a negative reversal payment row. Phase 4.1 created
     * the column unsigned; align it with the doc.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->bigInteger('amount')
                ->comment('Total received; negative on reversal rows (void flow)')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')
                ->comment('Total received')
                ->change();
        });
    }
};
