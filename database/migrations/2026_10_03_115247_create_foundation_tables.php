<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Format: 2026/2027');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->comment('Enum: AcademicYearStatus (planned/active/closed)');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('number')->comment('1 = ganjil (Jul-Dec), 2 = genap (Jan-Jun)');
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->comment('Enum: TermStatus (planned/active/closed)');
            $table->timestamps();

            $table->unique(['academic_year_id', 'number']);
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique()->comment('Format: 2026-07');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->comment('Enum: PeriodStatus (open/closed)');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('BOS, YYS, KOM, UMUM');
            $table->string('name');
            $table->string('type')->comment('Enum: FundType (pemerintah/yayasan/komite/lainnya)');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->comment('invoice, kwitansi, journal, surat_keluar, ppdb, asset');
            $table->string('period')->comment('2026 (per-year) or 2026-07 (per-month)');
            $table->string('prefix');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['key', 'period']);
        });

        Schema::create('calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('Enum: CalendarDayType (effektif/libur/ujian/kegiatan)');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_days');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('funds');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('academic_years');
    }
};
