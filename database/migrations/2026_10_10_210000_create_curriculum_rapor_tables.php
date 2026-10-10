<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('fase', 1)->comment('Fase A / B / C');
            $table->string('elemen')->comment('e.g. Bilangan, Literasi');
            $table->string('code')->comment('CP code, e.g. A.1');
            $table->text('description')->comment('From BSKAP CP documents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subject_id', 'fase', 'code']);
        });

        Schema::create('learning_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_achievement_id')->constrained()->cascadeOnDelete();
            $table->string('code')->comment('TP code, e.g. A.1.1');
            $table->text('description')->comment('Authored by teachers');
            $table->tinyInteger('semester')->comment('1 / 2');
            $table->tinyInteger('sequence')->comment('Teaching order');
            $table->timestamps();

            $table->unique(['learning_achievement_id', 'code']);
        });

        Schema::create('teacher_learning_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('employees')->restrictOnDelete();
            $table->date('date');
            $table->foreignId('learning_objective_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topic');
            $table->string('method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['classroom_id', 'date']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('e.g. Sumatif Tengah Semester');
            $table->string('type')->comment('Enum: AssessmentType (formatif/sumatif/sumatif_akhir)');
            $table->string('dimension')->comment('Enum: AssessmentDimension (pengetahuan/keterampilan)');
            $table->date('assessment_date');
            $table->decimal('max_score', 5, 2)->default(100);
            $table->foreignId('learning_objective_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['classroom_id', 'academic_term_id', 'type']);
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable()->comment('Numeric path');
            $table->string('predicate')->nullable()->comment('Rubric path (Fase A): BB/MB/BSH/SB');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id']);
        });

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->cascadeOnDelete();
            $table->string('status')->comment('Enum: ReportCardStatus (draft/diajukan/revisi/disetujui/diterbitkan)');
            $table->text('revision_note')->nullable()->comment('From kepala sekolah on revisi');
            $table->text('catatan_wali_kelas')->nullable();
            $table->tinyInteger('days_sick')->default(0);
            $table->tinyInteger('days_izin')->default(0);
            $table->tinyInteger('days_alpa')->default(0);
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'academic_term_id']);
        });

        Schema::create('report_card_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('final_score', 5, 2)->comment('Computed 40/60 (doc 06 §6)');
            $table->string('predicate')->comment('BB/MB/BSH/SB');
            $table->text('description')->comment('Auto-drafted, teacher-editable');
            $table->timestamps();

            $table->unique(['report_card_id', 'subject_id']);
        });

        Schema::create('extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('report_card_extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extracurricular_id')->constrained()->restrictOnDelete();
            $table->string('predicate')->comment('BB/MB/BSH/SB');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['report_card_id', 'extracurricular_id'], 'rapor_ekskul_unique');
        });

        Schema::create('report_card_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('e.g. Juara 1 MTQ Kecamatan');
            $table->string('type')->comment('Enum: AchievementType (akademik/non_akademik)');
            $table->string('level')->comment('Enum: AchievementLevel (sekolah/kecamatan/kabupaten/provinsi/nasional)');
            $table->tinyInteger('rank')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_achievements');
        Schema::dropIfExists('report_card_extracurriculars');
        Schema::dropIfExists('extracurriculars');
        Schema::dropIfExists('report_card_subjects');
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('teacher_learning_journals');
        Schema::dropIfExists('learning_objectives');
        Schema::dropIfExists('learning_achievements');
    }
};
