<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_no')->unique();
            $table->string('name');
            $table->string('nik')->nullable()->unique();
            $table->string('gender', 1)->comment('L / P');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->comment('Jabatan: Guru Kelas, Operator, Penjaga, ...');
            $table->string('employment_status')->comment('Enum: EmploymentStatus (pns/pppk/tetap/honorer/bsm)');
            $table->boolean('is_teaching')->default(false);
            $table->date('join_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bpjs_kesehatan_no')->nullable();
            $table->string('bpjs_ketenagakerjaan_no')->nullable();
            $table->string('npwp_no')->nullable();
            $table->unsignedBigInteger('base_salary')->default(0)->comment('Reference only; payroll computes from components');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('is_teaching');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
