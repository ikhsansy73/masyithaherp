<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('MTK, BIN, BIG, PAI, ...');
            $table->string('name');
            $table->string('kelompok', 1)->comment('Kelompok A / B (Kurikulum Merdeka)');
            $table->tinyInteger('jp_per_week')->comment('Jam pelajaran per week');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('class_subject_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('employees')->restrictOnDelete();
            $table->tinyInteger('jp_per_week')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'subject_id']);
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->string('day')->comment('Enum: ScheduleDay (senin..sabtu)');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['classroom_id', 'day', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('class_subject_teachers');
        Schema::dropIfExists('subjects');
    }
};
