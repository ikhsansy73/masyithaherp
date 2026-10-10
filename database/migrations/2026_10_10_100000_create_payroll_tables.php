<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master komponen gaji (doc 05 §2). gl_account_id nullable: baris
        // potongan hanya memakai liability_account_id.
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('GAJI_POKOK, BPJS_KES_PEG, ...');
            $table->string('name');
            $table->string('type')->comment('Enum: SalaryComponentType (pendapatan/potongan)');
            $table->string('calculation')->comment('Enum: SalaryCalculation (fixed/percent_base/manual_entry)');
            $table->unsignedBigInteger('default_amount')->default(0);
            $table->decimal('percent_rate', 5, 4)->nullable()->comment('0.0100 = 1% (data, bukan kode)');
            $table->boolean('is_employer')->default(false)->comment('BPJS bagian sekolah: biaya sekolah, tampil di slip');
            $table->foreignId('gl_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('liability_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Nominal per pegawai (override default master).
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('salary_component_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount')->default(0);
            $table->decimal('percent_rate', 5, 4)->nullable()->comment('Override rate master');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'salary_component_id'], 'employee_salary_components_pair_unique');
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('"Payroll 2026-07"');
            $table->unsignedTinyInteger('period_month')->comment('1-12');
            $table->unsignedSmallInteger('period_year');
            $table->string('status')->comment('Enum: PayrollStatus (draft/calculated/approved/paid/cancelled)');
            $table->unsignedBigInteger('total_gross')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->unsignedBigInteger('total_net')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('JE #9 akrual (approve)');
            $table->foreignId('payment_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete()->comment('JE #10 pembayaran');
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('base_salary')->default(0)->comment('Snapshot employees.base_salary');
            $table->unsignedBigInteger('total_earnings')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->unsignedBigInteger('net_salary')->default(0);
            $table->unsignedTinyInteger('days_present')->default(0);
            $table->unsignedTinyInteger('days_sick')->default(0);
            $table->unsignedTinyInteger('days_leave')->default(0);
            $table->unsignedTinyInteger('days_absent')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });

        Schema::create('payslip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->restrictOnDelete();
            $table->string('type')->comment('Enum: SalaryComponentType (pendapatan/potongan)');
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('description')->nullable()->comment('"Honor 12 JP"');
            $table->timestamps();

            $table->index('payslip_id');
        });

        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status')->comment('Enum: EmployeeAttendanceStatus (hadir/terlambat/izin/.../alpa)');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('type')->comment('Enum: LeaveType (izin/sakit/cuti/dinas)');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('days');
            $table->text('reason');
            $table->string('status')->comment('menunggu/disetujui/ditolak');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('employee_attendances');
        Schema::dropIfExists('payslip_items');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
    }
};
