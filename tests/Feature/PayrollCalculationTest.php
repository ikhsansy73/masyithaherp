<?php

namespace Tests\Feature;

use App\Enums\EmployeeAttendanceStatus;
use App\Enums\PayrollStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Payroll\EmployeeAttendanceService;
use App\Services\Payroll\PayrollService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\SalaryComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PayrollService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FundSeeder::class, AccountSeeder::class, SalaryComponentSeeder::class]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->user = User::factory()->create();
        $this->service = app(PayrollService::class);
    }

    /**
     * Fixture: GAJI_POKOK 3.000.000, TUNJ_JABATAN 1.000.000, TUNJ_TRANSPORT
     * 250.000; BPJS pegawai/sekolah use the master percent rates.
     */
    private function employeeWithComponents(): Employee
    {
        $employee = Employee::factory()->create(['base_salary' => 3_000_000]);

        $components = SalaryComponent::query()
            ->whereIn('code', [
                'GAJI_POKOK', 'TUNJ_JABATAN', 'TUNJ_TRANSPORT',
                'BPJS_KES_PEG', 'BPJS_TK_JHT_PEG', 'BPJS_KES_PSH', 'BPJS_TK_PSH',
            ])
            ->get()
            ->keyBy('code');

        foreach ([
            'GAJI_POKOK' => 3_000_000,
            'TUNJ_JABATAN' => 1_000_000,
            'TUNJ_TRANSPORT' => 250_000,
            'BPJS_KES_PEG' => 0,
            'BPJS_TK_JHT_PEG' => 0,
            'BPJS_KES_PSH' => 0,
            'BPJS_TK_PSH' => 0,
        ] as $code => $amount) {
            EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->getKey(),
                'salary_component_id' => $components[$code]->getKey(),
                'amount' => $amount,
            ]);
        }

        return $employee;
    }

    public function test_calculates_fixed_percent_employer_and_net(): void
    {
        $employee = $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);

        $this->assertSame(PayrollStatus::Draft, $period->status);
        $this->assertSame(1, $period->payslips()->count());

        $period = $this->service->calculate($period, $this->user);

        $payslip = $period->payslips()->first();

        $items = $payslip->items()->with('salaryComponent')->get()
            ->mapWithKeys(fn ($item): array => [$item->salaryComponent->code => $item]);

        $this->assertSame(3_000_000, $items['GAJI_POKOK']->amount);
        $this->assertSame(1_000_000, $items['TUNJ_JABATAN']->amount);
        $this->assertSame(250_000, $items['TUNJ_TRANSPORT']->amount);

        // 1% and 2% of the GAJI_POKOK configuration (3.000.000).
        $this->assertSame(30_000, $items['BPJS_KES_PEG']->amount);
        $this->assertSame(60_000, $items['BPJS_TK_JHT_PEG']->amount);

        // Employer share: 4% + 6,24% = 307.200 — not part of net pay.
        $this->assertSame(120_000, $items['BPJS_KES_PSH']->amount);
        $this->assertSame(187_200, $items['BPJS_TK_PSH']->amount);

        $this->assertSame(4_250_000, $payslip->total_earnings);
        $this->assertSame(90_000, $payslip->total_deductions);
        $this->assertSame(4_160_000, $payslip->net_salary);

        $period->refresh();

        $this->assertSame(PayrollStatus::Calculated, $period->status);
        $this->assertSame(4_250_000, $period->total_gross);
        $this->assertSame(90_000, $period->total_deductions);
        $this->assertSame(4_160_000, $period->total_net);
    }

    public function test_manual_entry_survives_recalculate_while_percent_rebuilds(): void
    {
        $employee = $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);

        $honor = SalaryComponent::query()->where('code', 'HONOR_PER_JAM')->firstOrFail();

        $this->service->addManualEntry(
            period: $period,
            employee: $employee,
            component: $honor,
            amount: 600_000,
            description: 'Honor 12 JP',
        );

        // Employee-specific BPJS override: 1% becomes 2%.
        EmployeeSalaryComponent::query()
            ->where('employee_id', $employee->getKey())
            ->where('salary_component_id', SalaryComponent::query()->where('code', 'BPJS_KES_PEG')->value('id'))
            ->update(['percent_rate' => 0.02]);

        $period = $this->service->calculate($period, $this->user);

        $items = $period->payslips()->first()->items()->with('salaryComponent')->get()
            ->mapWithKeys(fn ($item): array => [$item->salaryComponent->code => $item]);

        $this->assertSame(600_000, $items['HONOR_PER_JAM']->amount);
        $this->assertSame('Honor 12 JP', $items['HONOR_PER_JAM']->description);
        $this->assertSame(60_000, $items['BPJS_KES_PEG']->amount);
    }

    public function test_attendance_days_snapshot_into_payslip(): void
    {
        $employee = $this->employeeWithComponents();

        $attendance = app(EmployeeAttendanceService::class);

        $attendance->record($employee, Carbon::parse('2026-10-01'), EmployeeAttendanceStatus::Hadir);
        $attendance->record($employee, Carbon::parse('2026-10-02'), EmployeeAttendanceStatus::Terlambat);
        $attendance->record($employee, Carbon::parse('2026-10-05'), EmployeeAttendanceStatus::Izin);
        $attendance->record($employee, Carbon::parse('2026-10-06'), EmployeeAttendanceStatus::Alpa);

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);

        $payslip = $period->payslips()->first();

        $this->assertSame(2, $payslip->days_present);
        $this->assertSame(1, $payslip->days_leave);
        $this->assertSame(0, $payslip->days_sick);
        $this->assertSame(1, $payslip->days_absent);
    }

    public function test_deductions_exceeding_earnings_throws(): void
    {
        $employee = $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);

        $pph21 = SalaryComponent::query()->where('code', 'PPH21')->firstOrFail();

        // The retotal guard fires immediately on the manual entry, not on
        // the next calculate run.
        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Total potongan melebihi total pendapatan pada slip');

        $this->service->addManualEntry(
            period: $period,
            employee: $employee,
            component: $pph21,
            amount: 99_999_999,
        );
    }

    public function test_percent_component_without_gaji_pokok_throws(): void
    {
        $employee = Employee::factory()->create(['base_salary' => 2_000_000]);

        $bpjs = SalaryComponent::query()->where('code', 'BPJS_KES_PEG')->firstOrFail();

        EmployeeSalaryComponent::query()->create([
            'employee_id' => $employee->getKey(),
            'salary_component_id' => $bpjs->getKey(),
            'amount' => 0,
        ]);

        $period = $this->service->create(2026, 10, $this->user);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('belum memiliki komponen GAJI_POKOK');

        $this->service->calculate($period, $this->user);
    }
}
