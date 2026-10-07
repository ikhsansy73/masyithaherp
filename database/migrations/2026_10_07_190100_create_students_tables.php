<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique()->comment('Internal school number, from document_sequences');
            $table->string('nisn', 10)->nullable()->unique()->comment('National 10-digit number');
            $table->string('nik', 16)->nullable()->unique()->comment('National 16-digit number');
            $table->string('full_name');
            $table->string('gender', 1)->comment('L / P');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->comment('Enum: Religion (islam/kristen/katolik/hindu/buddha/konghucu)');
            $table->text('address')->nullable();
            $table->string('kk_no')->nullable()->comment('Family card number');
            $table->string('akta_no')->nullable()->comment('Birth certificate number');
            $table->string('phone')->nullable();
            $table->string('status')->comment('Enum: StudentStatus (aktif/lulus/mutasi_keluar/keluar/cadangan)');
            $table->date('entry_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->string('exit_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('full_name');
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->comment('Enum: GuardianRelation (ayah/ibu/wali)');
            $table->string('name');
            $table->string('nik', 16)->nullable();
            $table->string('occupation')->nullable();
            $table->string('education')->nullable()->comment('Enum: GuardianEducation (sd/smp/sma/d1-d4/s1/s2/s3)');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            // Plain index, NOT unique: one parent account may be guardian on
            // several students' rows; children are resolved via this relation.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'relationship']);
            $table->index('user_id');
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('Rombel name, e.g. 1A — unique per academic year');
            $table->tinyInteger('grade_level')->comment('1-6');
            $table->string('fase', 1)->comment('Fase A/B/C derived from grade, stored for query speed');
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->tinyInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('grade_level')->comment('Snapshot of the classroom grade');
            $table->string('status')->comment('Enum: EnrollmentStatus (aktif/pindah/keluar/lulus)');
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
            $table->index('classroom_id');
        });

        Schema::create('student_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('Enum: MovementType (kenaikan/tinggal_kelas/lulus/mutasi_masuk/mutasi_keluar/keluar)');
            $table->foreignId('from_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('to_classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->date('movement_date');
            $table->string('notes')->nullable();
            $table->foreignId('registered_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'type']);
        });

        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete()->comment('Snapshot of the day\'s class');
            $table->date('date');
            $table->string('status')->comment('Enum: StudentAttendanceStatus (hadir/sakit/izin/alpa)');
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'date']);
            $table->index(['classroom_id', 'date']);
        });

        Schema::create('ppdb_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no')->unique()->comment('PPDB/2026/000087');
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('applicant_name');
            $table->string('gender', 1)->comment('L / P');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable()->comment('Enum: Religion — copied to the student on acceptance');
            $table->string('nik', 16)->nullable();
            $table->string('origin_tk')->nullable()->comment('Asal TK/PAUD');
            $table->text('address')->nullable();
            $table->string('father_name');
            $table->string('mother_name');
            $table->string('parent_phone');
            $table->string('status')->comment('Enum: PpdbStatus (baru/verifikasi/diterima/cadangan/ditolak/terdaftar)');
            $table->timestamp('registered_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_registrations');
        Schema::dropIfExists('student_attendances');
        Schema::dropIfExists('student_movements');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
    }
};
