<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Exceptions\AccountingException;
use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Payroll\PayrollService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\SalaryComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollWorkflowTest extends TestCase
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
        ] as $code => $amount) {
            EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->getKey(),
                'salary_component_id' => $components[$code]->getKey(),
                'amount' => $amount,
            ]);
        }

        foreach (['BPJS_KES_PEG', 'BPJS_TK_JHT_PEG', 'BPJS_KES_PSH', 'BPJS_TK_PSH'] as $code) {
            EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->getKey(),
                'salary_component_id' => $components[$code]->getKey(),
                'amount' => 0,
            ]);
        }

        return $employee;
    }

    public function test_full_cycle_posts_both_journal_entries_and_locks(): void
    {
        $this->employeeWithComponents();

        $cashAccount = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);
        $period = $this->service->approve($period, $this->user);

        $period->refresh();

        $this->assertSame(PayrollStatus::Approved, $period->status);
        $this->assertTrue($period->status->isLocked());
        $this->assertNotNull($period->journal_entry_id);
        $this->assertMatchesRegularExpression('/^JE\/2026-10\/\d{6}$/', $period->journalEntry->number);

        // JE #9: Dr expense (earnings + employer BPJS), Cr utang (potongan
        // + employer share), Cr utang gaji = net.
        $lines = $period->journalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        $this->assertSame(3_000_000, $lines['5-1110']->debit);
        $this->assertSame(1_250_000, $lines['5-1120']->debit);
        $this->assertSame(307_200, $lines['5-1140']->debit);
        $this->assertSame(397_200, $lines['2-1200']->credit);
        $this->assertSame(4_160_000, $lines['2-1100']->credit);

        $entry = $this->service->pay(
            period: $period,
            cashAccount: $cashAccount,
            paymentDate: Carbon::today(),
            actor: $this->user,
        );

        $entry->refresh();

        $this->assertSame(PayrollStatus::Paid, $entry->status);
        $this->assertNotNull($entry->paid_at);
        $this->assertNotNull($entry->payment_journal_entry_id);

        // JE #10: Dr utang gaji = net, Cr kas/bank.
        $paymentLines = $entry->paymentJournalEntry->lines->mapWithKeys(
            fn ($line): array => [$line->account->code => $line],
        );

        $this->assertSame(4_160_000, $paymentLines['2-1100']->debit);
        $this->assertSame(4_160_000, $paymentLines['1-1100']->credit);
    }

    public function test_approve_requires_calculated(): void
    {
        $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Payroll harus dihitung terlebih dahulu');

        $this->service->approve($period, $this->user);
    }

    public function test_pay_requires_approved(): void
    {
        $this->employeeWithComponents();

        $cashAccount = CashAccount::factory()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
        ]);

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('Hanya payroll yang sudah disetujui dapat dibayar');

        $this->service->pay($period, $cashAccount, Carbon::today(), $this->user);
    }

    public function test_approved_period_is_locked(): void
    {
        $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);
        $period = $this->service->approve($period, $this->user);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('dan sudah dikunci');

        $this->service->calculate($period, $this->user);
    }

    public function test_manual_entry_rejected_after_approval(): void
    {
        $employee = $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->calculate($period, $this->user);
        $period = $this->service->approve($period, $this->user);

        $honor = SalaryComponent::query()->where('code', 'HONOR_PER_JAM')->firstOrFail();

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('dan sudah dikunci');

        $this->service->addManualEntry(
            period: $period,
            employee: $employee,
            component: $honor,
            amount: 100_000,
        );
    }

    public function test_cancel_only_from_editable_status(): void
    {
        $this->employeeWithComponents();

        $period = $this->service->create(2026, 10, $this->user);
        $period = $this->service->cancel($period, $this->user);

        $this->assertSame(PayrollStatus::Cancelled, $period->status);
    }

    public function test_duplicate_period_name_throws(): void
    {
        $this->employeeWithComponents();

        $this->service->create(2026, 10, $this->user);

        $this->expectException(AccountingException::class);
        $this->expectExceptionMessage('sudah ada');

        $this->service->create(2026, 10, $this->user);
    }

    public function test_snapshot_includes_only_active_employees(): void
    {
        $this->employeeWithComponents();

        Employee::factory()->create(['is_active' => false]);

        $period = $this->service->create(2026, 10, $this->user);

        $this->assertSame(1, $period->payslips()->count());
    }
}
