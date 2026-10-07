<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Doc 02 §3 fee chain, created in Phase 3 because the PPDB "Daftarkan"
     * flow assigns default student fees in the same transaction (doc 06 §2).
     * The billing resources and services arrive in Phase 4.
     */
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('SPP, DSP, SERAGAM, BUKU, KEGIATAN, WISUDA');
            $table->string('name');
            $table->string('category')->comment('Enum: FeeCategory (bulanan/tahunan/insidental)');
            $table->foreignId('revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('grade_level')->nullable()->comment('1-6; null = all grades (flat)');
            $table->foreignId('fund_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->unique(['academic_year_id', 'fee_type_id', 'grade_level']);
        });

        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount')->comment('May deviate from structure (exception case)');
            $table->tinyInteger('months')->default(1)->comment('12 for SPP; 1 for tahunan/insidental');
            $table->tinyInteger('first_month')->nullable()->comment('SPP starting month for mid-year entrants');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'fee_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_types');
    }
};
