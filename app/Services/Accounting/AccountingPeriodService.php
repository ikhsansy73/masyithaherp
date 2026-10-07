<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalSource;
use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tutup buku flow (doc 03 §5): integrity checks, the auto closing JE
 * (posting rule #17), and reopen (super_admin via accounting.period.reopen).
 */
class AccountingPeriodService
{
    public function __construct(
        private readonly JournalPostingService $journals,
    ) {}

    /**
     * Close a period: verify integrity, post the closing JE, lock the month.
     *
     * @throws AccountingException
     */
    public function close(AccountingPeriod $period, int $userId): AccountingPeriod
    {
        $label = $this->label($period);

        if ($period->status === PeriodStatus::Closed) {
            throw new AccountingException("Periode {$label} sudah ditutup.");
        }

        return DB::transaction(function () use ($period, $userId, $label): AccountingPeriod {
            $period = AccountingPeriod::query()
                ->whereKey($period->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // --- Integrity checks (doc 03 §5 step 1) ---

            // Any entry with debit <> credit is corruption (the posting
            // service prevents it; the DB CHECK constrains single lines).
            // Void pairs are included: a mirrored pair nets to zero, so
            // only genuinely corrupt entries trip this check.
            $unbalanced = JournalEntry::query()
                ->where('accounting_period_id', $period->getKey())
                ->whereHas('lines', function ($query): void {
                    $query->groupBy('journal_entry_id')
                        ->havingRaw('SUM(debit) <> SUM(credit)');
                })
                ->exists();

            if ($unbalanced) {
                throw new AccountingException("Terdapat jurnal tidak seimbang pada periode {$label}.");
            }

            $this->assertNoDrafts($period, $label);

            // --- Closing JE (doc 03 §5 step 2, posting rule #17) ---

            $this->postClosingEntry($period, $userId);

            $period->forceFill([
                'status' => PeriodStatus::Closed,
                'closed_at' => now(),
                'closed_by' => $userId,
            ])->save();

            return $period;
        });
    }

    /**
     * Reopen a closed period: void the closing JE, reopen, and log.
     *
     * @throws AccountingException
     */
    public function reopen(AccountingPeriod $period, int $userId): AccountingPeriod
    {
        $label = $this->label($period);

        if ($period->status === PeriodStatus::Open) {
            throw new AccountingException("Periode {$label} belum ditutup.");
        }

        return DB::transaction(function () use ($period, $userId, $label): AccountingPeriod {
            $period = AccountingPeriod::query()
                ->whereKey($period->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // The void of the closing JE is subject to period rules, so the
            // period must be open first — inside this transaction, so any
            // failure rolls the whole reopen back (period stays closed).
            $period->forceFill([
                'status' => PeriodStatus::Open,
                'closed_at' => null,
                'closed_by' => null,
            ])->save();

            $closing = $this->closingEntry($period);

            if ($closing !== null) {
                $this->journals->void($closing, "Buka kembali periode {$label}.");
            }

            activity()
                ->performedOn($period)
                ->causedBy(User::find($userId))
                ->log("Periode {$label} dibuka kembali.");

            return $period;
        });
    }

    /**
     * The auto closing JE of a period (reference = the period model). The
     * description prefix distinguishes it from the reversal entry created
     * when a reopen voids it (the reversal carries the same reference).
     */
    public function closingEntry(AccountingPeriod $period): ?JournalEntry
    {
        return JournalEntry::query()
            ->where('reference_type', (new AccountingPeriod)->getMorphClass())
            ->where('reference_id', $period->getKey())
            ->where('description', 'like', 'Jurnal penutup %')
            ->posted()
            ->first();
    }

    /**
     * Posting rule #17: close revenue and expenses into 3-1200 as one JE
     * whose lines keep each balance's fund dimension.
     */
    private function postClosingEntry(AccountingPeriod $period, int $userId): void
    {
        // All entries, regardless of status: a void entry and its mirrored
        // reversal net to zero, so the balances below are the true ledger.
        $balances = JournalLine::query()
            ->selectRaw('journal_lines.account_id, journal_lines.fund_id, accounts.type as account_type, '
                .'SUM(journal_lines.credit) - SUM(journal_lines.debit) as balance')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('accounts.type', [AccountType::Pendapatan->value, AccountType::Beban->value])
            ->where('journal_entries.accounting_period_id', $period->getKey())
            ->groupBy('journal_lines.account_id', 'journal_lines.fund_id', 'accounts.type')
            ->get();

        $surplus = Account::query()->where('code', '3-1200')->firstOrFail();

        $lines = [];
        $revenueFunds = [];
        $expenseFunds = [];

        foreach ($balances as $row) {
            // balance = credit - debit: positive for credit-normal revenue,
            // negative for debit-normal expense.
            $balance = (int) $row->balance;

            if ($balance === 0) {
                continue;
            }

            if ($row->account_type === AccountType::Pendapatan->value) {
                // Credit-normal revenue closes with a debit.
                $lines[] = JournalDraftLine::debit($row->account_id, $balance, fundId: $row->fund_id);
                $revenueFunds[$row->fund_id ?? 0] = ($revenueFunds[$row->fund_id ?? 0] ?? 0) + $balance;
            } else {
                // Debit-normal expense closes with a credit of debit - credit.
                $lines[] = JournalDraftLine::credit($row->account_id, -$balance, fundId: $row->fund_id);
                $expenseFunds[$row->fund_id ?? 0] = ($expenseFunds[$row->fund_id ?? 0] ?? 0) - $balance;
            }
        }

        if ($lines === []) {
            return;
        }

        // Surplus/defisit netted per fund onto 3-1200: positive = surplus
        // (credit), negative = defisit (debit).
        $surplusByFund = [];

        foreach ($revenueFunds as $fundId => $amount) {
            $surplusByFund[$fundId] = ($surplusByFund[$fundId] ?? 0) + $amount;
        }

        foreach ($expenseFunds as $fundId => $amount) {
            $surplusByFund[$fundId] = ($surplusByFund[$fundId] ?? 0) - $amount;
        }

        foreach ($surplusByFund as $fundId => $amount) {
            if ($amount === 0) {
                continue;
            }

            $lines[] = $amount > 0
                ? JournalDraftLine::credit($surplus->id, $amount, fundId: $fundId === 0 ? null : $fundId)
                : JournalDraftLine::debit($surplus->id, -$amount, fundId: $fundId === 0 ? null : $fundId);
        }

        // Close on the period's last day, or today when the month is not
        // over yet (mid-month tutup buku).
        $date = $period->ends_at->copy()->startOfDay();

        if ($date->greaterThan(today())) {
            $date = today();
        }

        $this->journals->post(new JournalDraft(
            entryDate: $date,
            description: 'Jurnal penutup '.$this->label($period),
            userId: $userId,
            source: JournalSource::Otomatis,
            reference: $period,
            lines: $lines,
        ));
    }

    /**
     * Billing (Phase 4) and payroll (Phase 5) draft documents for the month
     * must be resolved before the month can close. The tables arrive in
     * those phases; this check activates automatically.
     */
    private function assertNoDrafts(AccountingPeriod $period, string $label): void
    {
        $checks = [
            'invoice_batches' => [
                'message' => 'batch tagihan',
                'yearColumn' => 'academic_year_id',
                'yearValue' => $period->academic_year_id,
                'monthColumn' => 'period_month',
            ],
            'payroll_periods' => [
                'message' => 'periode payroll',
                'yearColumn' => 'period_year',
                'yearValue' => $period->starts_at->year,
                'monthColumn' => 'period_month',
            ],
        ];

        foreach ($checks as $table => $config) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $exists = DB::table($table)
                ->where('status', 'draft')
                ->where($config['yearColumn'], $config['yearValue'])
                ->where($config['monthColumn'], $period->starts_at->month)
                ->exists();

            if ($exists) {
                throw new AccountingException(
                    "Masih ada {$config['message']} berstatus draft pada periode {$label}."
                );
            }
        }
    }

    private function label(AccountingPeriod $period): string
    {
        $date = Carbon::parse($period->name.'-01')->locale(config('app.locale'));

        return $date->translatedFormat('F Y');
    }
}
