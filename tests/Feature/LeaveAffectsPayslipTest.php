<?php

namespace Tests\Feature;

use App\Enums\EmployeeAttendanceStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Payroll\LeaveService;
use App\Services\Payroll\PayrollService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\SalaryComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveAffectsPayslipTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LeaveService $service;

    private PayrollService $payroll;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class, SalaryComponentSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();
        $this->service = app(LeaveService::class);
        $this->payroll = app(PayrollService::class);
    }

    private function employeeWithBase(): Employee
    {
        $employee = Employee::factory()->create(['base_salary' => 3_000_000]);

        $components = SalaryComponent::query()
            ->whereIn('code', ['GAJI_POKOK', 'BPJS_KES_PEG', 'BPJS_TK_JHT_PEG'])
            ->get()
            ->keyBy('code');

        foreach (['GAJI_POKOK' => 3_000_000, 'BPJS_KES_PEG' => 0, 'BPJS_TK_JHT_PEG' => 0] as $code => $amount) {
            EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->getKey(),
                'salary_component_id' => $components[$code]->getKey(),
                'amount' => $amount,
            ]);
        }

        return $employee;
    }

    public function test_approved_leave_writes_attendance_and_counts_in_payslip(): void
    {
        $employee = $this->employeeWithBase();

        // 2026-10-05 (Mon) and 2026-10-06 (Tue) — two weekdays.
        $leave = $this->service->create($employee, [
            'type' => LeaveType::Izin,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'days' => 2,
            'reason' => 'Keperluan keluarga',
        ]);

        $leave = $this->service->approve($leave, $this->user);

        $this->assertSame(LeaveStatus::Disetujui, $leave->status);
        $this->assertSame($this->user->getKey(), $leave->approved_by);
        $this->assertNotNull($leave->approved_at);

        $rows = EmployeeAttendance::query()
            ->where('employee_id', $employee->getKey())
            ->orderBy('date')
            ->get();

        $this->assertSame(2, $rows->count());
        $this->assertSame('2026-10-05', $rows[0]->date->toDateString());
        $this->assertSame(EmployeeAttendanceStatus::Izin, $rows[0]->status);
        $this->assertStringContainsString('Izin', (string) $rows[0]->notes);

        $period = $this->payroll->create(2026, 10, $this->user);
        $period = $this->payroll->calculate($period, $this->user);

        $payslip = $period->payslips()->first();

        $this->assertSame(2, $payslip->days_leave);
        $this->assertSame(0, $payslip->days_present);
    }

    public function test_leave_spanning_sunday_skips_sunday(): void
    {
        $employee = $this->employeeWithBase();

        // 2026-10-04 is a Sunday.
        $leave = $this->service->create($employee, [
            'type' => LeaveType::Sakit,
            'start_date' => '2026-10-04',
            'end_date' => '2026-10-06',
            'days' => 3,
            'reason' => 'Demam',
        ]);

        $this->service->approve($leave, $this->user);

        $dates = EmployeeAttendance::query()
            ->where('employee_id', $employee->getKey())
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date): string => $date->toDateString())
            ->all();

        $this->assertSame(['2026-10-05', '2026-10-06'], $dates);
        $this->assertSame(
            EmployeeAttendanceStatus::Sakit,
            EmployeeAttendance::query()->where('employee_id', $employee->getKey())->first()->status,
        );
    }

    public function test_rejected_leave_writes_no_attendance(): void
    {
        $employee = $this->employeeWithBase();

        $leave = $this->service->create($employee, [
            'type' => LeaveType::Cuti,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'days' => 1,
            'reason' => 'Acara keluarga',
        ]);

        $leave = $this->service->reject($leave, $this->user);

        $this->assertSame(LeaveStatus::Ditolak, $leave->status);
        $this->assertSame(0, EmployeeAttendance::query()->where('employee_id', $employee->getKey())->count());

        $period = $this->payroll->create(2026, 10, $this->user);
        $period = $this->payroll->calculate($period, $this->user);

        $this->assertSame(0, $period->payslips()->first()->days_leave);
    }

    public function test_overlapping_leave_throws(): void
    {
        $employee = $this->employeeWithBase();

        $this->service->create($employee, [
            'type' => LeaveType::Izin,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'days' => 2,
            'reason' => 'Pertama',
        ]);

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('beririsan');

        $this->service->create($employee, [
            'type' => LeaveType::Sakit,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-08',
            'days' => 3,
            'reason' => 'Kedua',
        ]);
    }

    public function test_end_before_start_throws(): void
    {
        $employee = $this->employeeWithBase();

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('Tanggal selesai tidak boleh sebelum tanggal mulai');

        $this->service->create($employee, [
            'type' => LeaveType::Izin,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-05',
            'days' => 1,
            'reason' => 'Terbalik',
        ]);
    }

    public function test_processed_leave_cannot_be_reapproved(): void
    {
        $employee = $this->employeeWithBase();

        $leave = $this->service->create($employee, [
            'type' => LeaveType::Izin,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'days' => 1,
            'reason' => 'Sudah diproses',
        ]);

        $this->service->approve($leave, $this->user);

        $this->expectException(SchoolException::class);
        $this->expectExceptionMessage('sudah diproses');

        $this->service->approve($leave, $this->user);
    }
}
