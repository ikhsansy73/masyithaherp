<?php

namespace App\Services\Payroll;

use App\Enums\JournalSource;
use App\Enums\PayrollStatus;
use App\Enums\SalaryCalculation;
use App\Enums\SalaryComponentType;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Payroll run lifecycle (doc 05 §3): draft → calculated → approved → paid.
 * Approval posts the accrual JE (rule #9), payment posts the payout JE
 * (rule #10). Approved periods are locked.
 */
class PayrollService
{
    public function __construct(
        private readonly JournalPostingService $journals,
    ) {}

    /**
     * Create the period for month M and snapshot active employees into
     * payslips.
     */
    public function create(int $year, int $month, User $actor): PayrollPeriod
    {
        if ($month < 1 || $month > 12) {
            throw new AccountingException('Bulan payroll harus antara 1 dan 12.');
        }

        $name = sprintf('Payroll %d-%02d', $year, $month);

        if (PayrollPeriod::query()->where('name', $name)->exists()) {
            throw new AccountingException("Periode payroll untuk {$name} sudah ada.");
        }

        return DB::transaction(function () use ($year, $month, $name, $actor): PayrollPeriod {
            $period = PayrollPeriod::query()->create([
                'name' => $name,
                'period_year' => $year,
                'period_month' => $month,
                'status' => PayrollStatus::Draft,
            ]);

            $this->snapshotEmployees($period, $actor);

            return $period;
        });
    }

    /**
     * Add payslips for active employees missing one (new hires since the
     * period was created).
     */
    public function syncEmployees(PayrollPeriod $period, User $actor): int
    {
        $this->assertEditable($period);

        return $this->snapshotEmployees($period, $actor);
    }

    /**
     * Per payslip: rebuild items from employee_salary_components (+ manual
     * entries preserved), snapshot attendance days, re-total. Idempotent —
     * re-calculate wipes and rebuilds automatic items while still editable.
     */
    public function calculate(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        $this->assertEditable($period);

        return DB::transaction(function () use ($period, $actor): PayrollPeriod {
            $period = $this->lockPeriod($period);

            $this->assertEditable($period);

            $from = $period->startsAt();
            $to = $period->endsAt();

            foreach ($period->payslips()->with('employee')->get() as $payslip) {
                $this->calculatePayslip($payslip, $from, $to);
            }

            $items = PayslipItem::query()
                ->whereIn('payslip_id', $period->payslips()->select('id'))
                ->with('salaryComponent')
                ->get();

            $gross = $items
                ->filter(fn (PayslipItem $item): bool => $item->type === SalaryComponentType::Pendapatan
                    && ! $item->salaryComponent->is_employer)
                ->sum('amount');
            $deductions = $items->where('type', SalaryComponentType::Potongan)->sum('amount');

            $period->forceFill([
                'total_gross' => $gross,
                'total_deductions' => $deductions,
                'total_net' => $gross - $deductions,
                'status' => PayrollStatus::Calculated,
                'calculated_at' => now(),
            ])->save();

            activity()->performedOn($period)->causedBy($actor)->log('Payroll dihitung');

            return $period;
        });
    }

    /**
     * JE #9 (rule #9): Dr expense accounts per earnings component (employer
     * BPJS → 5-1140), Cr utang accounts per deduction + employer share, and
     * the balancing Cr 2-1100 Utang Gaji for the net payable. One JE per
     * period. Locked afterwards.
     */
    public function approve(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        if ($period->status !== PayrollStatus::Calculated) {
            throw new AccountingException('Payroll harus dihitung terlebih dahulu sebelum disetujui.');
        }

        return DB::transaction(function () use ($period, $actor): PayrollPeriod {
            $period = $this->lockPeriod($period);

            if ($period->status !== PayrollStatus::Calculated) {
                throw new AccountingException('Payroll harus dihitung terlebih dahulu sebelum disetujui.');
            }

            $lines = $this->buildAccrualLines($period);

            $entry = $this->journals->post(new JournalDraft(
                entryDate: Carbon::today(),
                description: "Payroll {$period->monthLabel()} — akrual beban gaji",
                userId: $actor->getKey(),
                source: JournalSource::Otomatis,
                reference: $period,
                lines: $lines->values()->all(),
            ));

            $period->forceFill([
                'status' => PayrollStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $actor->getKey(),
                'journal_entry_id' => $entry->getKey(),
            ])->save();

            return $period;
        });
    }

    /**
     * JE #10 (rule #10): Dr 2-1100 Utang Gaji, Cr kas/bank. Slips are then
     * downloadable per employee.
     */
    public function pay(PayrollPeriod $period, CashAccount $cashAccount, Carbon $paymentDate, User $actor): PayrollPeriod
    {
        if ($period->status !== PayrollStatus::Approved) {
            throw new AccountingException('Hanya payroll yang sudah disetujui dapat dibayar.');
        }

        if (! $cashAccount->is_active) {
            throw new AccountingException('Kas/bank tidak aktif atau tidak ditemukan.');
        }

        if ($period->total_net <= 0) {
            throw new AccountingException('Total gaji bersih harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($period, $cashAccount, $paymentDate, $actor): PayrollPeriod {
            $period = $this->lockPeriod($period);

            if ($period->status !== PayrollStatus::Approved) {
                throw new AccountingException('Hanya payroll yang sudah disetujui dapat dibayar.');
            }

            $entry = $this->journals->post(new JournalDraft(
                entryDate: $paymentDate->copy()->startOfDay(),
                description: "Pembayaran Payroll {$period->monthLabel()}",
                userId: $actor->getKey(),
                source: JournalSource::Otomatis,
                reference: $period,
                lines: [
                    JournalDraftLine::debit(
                        static::utangGajiAccountId(),
                        $period->total_net,
                        null,
                        'Pembayaran gaji karyawan',
                    ),
                    JournalDraftLine::credit($cashAccount->account_id, $period->total_net),
                ],
            ));

            $period->forceFill([
                'status' => PayrollStatus::Paid,
                'paid_at' => now(),
                'payment_journal_entry_id' => $entry->getKey(),
            ])->save();

            return $period;
        });
    }

    /**
     * Cancel — only from draft/calculated, never after approval.
     */
    public function cancel(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        $this->assertEditable($period);

        $period->forceFill(['status' => PayrollStatus::Cancelled])->save();

        activity()->performedOn($period)->causedBy($actor)->log('Payroll dibatalkan');

        return $period;
    }

    /**
     * A manual payslip line (HONOR_PER_JAM, BONUS_THR, PPH21, …) — only
     * while the period is still editable. Replaces any existing item for
     * the same component on the payslip.
     */
    public function addManualEntry(
        PayrollPeriod $period,
        Employee $employee,
        SalaryComponent $component,
        int $amount,
        ?string $description = null,
    ): PayslipItem {
        $this->assertEditable($period);

        if ($component->calculation !== SalaryCalculation::ManualEntry) {
            throw new AccountingException("Komponen {$component->code} bukan komponen entri manual.");
        }

        if ($amount <= 0) {
            throw new AccountingException('Nilai komponen manual harus lebih besar dari nol.');
        }

        $payslip = $period->payslips()->where('employee_id', $employee->getKey())->first();

        if ($payslip === null) {
            throw new AccountingException("Pegawai {$employee->name} tidak ada pada periode ini.");
        }

        $item = PayslipItem::query()->updateOrCreate(
            [
                'payslip_id' => $payslip->getKey(),
                'salary_component_id' => $component->getKey(),
            ],
            [
                'type' => $component->type,
                'amount' => $amount,
                'description' => $description,
            ],
        );

        // Keep the stored totals in sync without a full recalculate.
        $this->retotalPayslip($payslip);

        return $item;
    }

    /**
     * Remove a manual payslip line while the period is editable.
     */
    public function removeManualEntry(PayrollPeriod $period, PayslipItem $item): void
    {
        $this->assertEditable($period);

        if ($item->salaryComponent->calculation !== SalaryCalculation::ManualEntry) {
            throw new AccountingException('Hanya komponen entri manual yang dapat dihapus.');
        }

        $item->delete();

        $this->retotalPayslip($item->payslip);
    }

    /**
     * HONOR_PER_JAM math: JP × the employee's configured rate. The rate
     * falls back to the component default when the employee has none.
     */
    public static function manualRate(Employee $employee, SalaryComponent $component): int
    {
        $config = EmployeeSalaryComponent::query()
            ->where('employee_id', $employee->getKey())
            ->where('salary_component_id', $component->getKey())
            ->where('is_active', true)
            ->first();

        return (int) ($config?->amount ?? $component->default_amount);
    }

    private function calculatePayslip(Payslip $payslip, Carbon $from, Carbon $to): void
    {
        $employee = $payslip->employee;

        $configs = EmployeeSalaryComponent::query()
            ->where('employee_id', $employee->getKey())
            ->where('is_active', true)
            ->whereHas('salaryComponent', fn ($query) => $query->where('is_active', true))
            ->with('salaryComponent')
            ->get();

        $base = $this->gajiPokokAmount($employee, $configs);

        // Automatic items are rebuilt; manual entries survive re-runs.
        PayslipItem::query()
            ->where('payslip_id', $payslip->getKey())
            ->whereHas('salaryComponent', fn ($query) => $query->where('calculation', '!=', SalaryCalculation::ManualEntry->value))
            ->delete();

        foreach ($configs as $config) {
            $component = $config->salaryComponent;

            if ($component->calculation === SalaryCalculation::ManualEntry) {
                continue;
            }

            $amount = $component->calculation === SalaryCalculation::PercentBase
                ? (int) round($base * (float) ($config->percent_rate ?? $component->percent_rate ?? 0))
                : $config->amount;

            if ($amount <= 0) {
                continue;
            }

            $payslip->items()->updateOrCreate(
                ['salary_component_id' => $component->getKey()],
                [
                    'type' => $component->type,
                    'amount' => $amount,
                    'description' => $component->calculation === SalaryCalculation::PercentBase
                        ? sprintf('%s (%s%% gaji pokok)', $component->name, rtrim(rtrim((string) ((float) ($config->percent_rate ?? $component->percent_rate) * 100), '0'), '.'))
                        : $component->name,
                ],
            );
        }

        $this->applyAttendanceDays($payslip, $from, $to);
        $this->retotalPayslip($payslip);
    }

    /**
     * Net must stay non-negative per payslip — deductions beyond earnings
     * break JE #9's balancing credit.
     */
    private function retotalPayslip(Payslip $payslip): void
    {
        $items = $payslip->items()->with('salaryComponent')->get();

        $earnings = $items
            ->filter(fn (PayslipItem $item): bool => $item->type === SalaryComponentType::Pendapatan
                && ! $item->salaryComponent->is_employer)
            ->sum('amount');
        $deductions = $items->where('type', SalaryComponentType::Potongan)->sum('amount');

        if ($deductions > $earnings) {
            throw new AccountingException('Total potongan melebihi total pendapatan pada slip '.$payslip->employee->name.'.');
        }

        $payslip->forceFill([
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'net_salary' => $earnings - $deductions,
        ])->save();
    }

    /**
     * Days present/sick/leave/absent snapshot from employee_attendances
     * inside the payroll month (doc 05 §4).
     */
    private function applyAttendanceDays(Payslip $payslip, Carbon $from, Carbon $to): void
    {
        $counts = $payslip->employee->attendances()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $payslip->days_present = ($counts['hadir'] ?? 0) + ($counts['terlambat'] ?? 0) + ($counts['dinas_luar'] ?? 0);
        $payslip->days_sick = $counts['sakit'] ?? 0;
        $payslip->days_leave = ($counts['izin'] ?? 0) + ($counts['cuti'] ?? 0);
        $payslip->days_absent = $counts['alpa'] ?? 0;
    }

    /**
     * Percent components (BPJS) compute on the GAJI_POKOK configuration —
     * never on employees.base_salary, which is display only (doc 05 §1).
     */
    private function gajiPokokAmount(Employee $employee, Collection $configs): int
    {
        $base = $configs->first(fn (EmployeeSalaryComponent $config): bool => $config->salaryComponent->code === 'GAJI_POKOK');

        $hasPercent = $configs->contains(fn (EmployeeSalaryComponent $config): bool => $config->salaryComponent->calculation === SalaryCalculation::PercentBase);

        if ($hasPercent && $base === null) {
            throw new AccountingException("Pegawai {$employee->name} belum memiliki komponen GAJI_POKOK untuk perhitungan BPJS.");
        }

        return (int) ($base?->amount ?? 0);
    }

    /**
     * Aggregated JE #9 lines across all payslips of the period. The net
     * payable lands as the balancing credit on 2-1100 (after potongan
     * lines, which share the same utang account).
     *
     * @return Collection<int, JournalDraftLine>
     */
    private function buildAccrualLines(PayrollPeriod $period): Collection
    {
        $items = PayslipItem::query()
            ->whereIn('payslip_id', $period->payslips()->select('id'))
            ->with('salaryComponent')
            ->get();

        if ($items->isEmpty()) {
            throw new AccountingException('Tidak ada komponen gaji untuk diposting pada periode ini.');
        }

        $debits = [];
        $credits = [];

        foreach ($items as $item) {
            $component = $item->salaryComponent;

            if ($component->type === SalaryComponentType::Pendapatan) {
                if ($component->gl_account_id === null) {
                    throw new AccountingException("Komponen {$component->code} belum memiliki akun beban.");
                }

                $debits[$component->gl_account_id] = ($debits[$component->gl_account_id] ?? 0) + $item->amount;

                if ($component->is_employer) {
                    $liabilityId = $component->liability_account_id;

                    if ($liabilityId === null) {
                        throw new AccountingException("Komponen {$component->code} (bagian sekolah) belum memiliki akun utang.");
                    }

                    $credits[$liabilityId] = ($credits[$liabilityId] ?? 0) + $item->amount;
                }
            } else {
                $liabilityId = $component->liability_account_id;

                if ($liabilityId === null) {
                    throw new AccountingException("Komponen {$component->code} belum memiliki akun utang.");
                }

                $credits[$liabilityId] = ($credits[$liabilityId] ?? 0) + $item->amount;
            }
        }

        $totalDebits = array_sum($debits);
        $totalCredits = array_sum($credits);
        $net = $totalDebits - $totalCredits;

        if ($net < 0) {
            throw new AccountingException('Total potongan melebihi total pendapatan pada periode ini.');
        }

        if ($totalDebits <= 0) {
            throw new AccountingException('Tidak ada beban gaji untuk diposting pada periode ini.');
        }

        $lines = collect();

        foreach ($debits as $accountId => $amount) {
            $lines->push(JournalDraftLine::debit($accountId, $amount, null, 'Beban gaji karyawan'));
        }

        foreach ($credits as $accountId => $amount) {
            $lines->push(JournalDraftLine::credit($accountId, $amount));
        }

        if ($net > 0) {
            $lines->push(JournalDraftLine::credit(
                static::utangGajiAccountId(),
                $net,
                null,
                'Gaji bersih karyawan',
            ));
        }

        return $lines;
    }

    private function snapshotEmployees(PayrollPeriod $period, User $actor): int
    {
        $existing = $period->payslips()->pluck('employee_id')->all();

        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotIn('id', $existing)
            ->get(['id', 'base_salary']);

        foreach ($employees as $employee) {
            $period->payslips()->create([
                'employee_id' => $employee->getKey(),
                'base_salary' => $employee->base_salary,
            ]);
        }

        return $employees->count();
    }

    private function lockPeriod(PayrollPeriod $period): PayrollPeriod
    {
        return PayrollPeriod::query()
            ->whereKey($period->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertEditable(PayrollPeriod $period): void
    {
        if (! $period->status->isEditable()) {
            throw new AccountingException("Periode payroll {$period->name} berstatus {$period->status->label()} dan sudah dikunci.");
        }
    }

    private static function utangGajiAccountId(): int
    {
        return (int) Account::query()->where('code', '2-1100')->value('id');
    }
}
