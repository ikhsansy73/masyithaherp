<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Services\Shared\DocumentSequenceService;
use Illuminate\Support\Facades\DB;

/**
 * The only write path for journals (doc 03 §3). Every business event
 * (payments, payroll, depreciation, …) funnels through post(); corrections
 * go through void(), which creates a mirrored reversal entry.
 */
class JournalPostingService
{
    public function __construct(
        private readonly DocumentSequenceService $sequences,
    ) {}

    /**
     * Validate and post a draft. Validation order follows doc 03 §3.1;
     * each failure throws AccountingException with an Indonesian message.
     */
    public function post(JournalDraft $draft): JournalEntry
    {
        $date = $draft->entryDate->copy()->startOfDay();

        // 1. No future entries.
        if ($date->greaterThan(today())) {
            throw new AccountingException('Tanggal jurnal tidak boleh di masa depan.');
        }

        // 2. The date must fall inside an open accounting period.
        $period = AccountingPeriod::query()
            ->where('name', $date->format('Y-m'))
            ->first();

        if ($period === null) {
            $label = $date->locale(config('app.locale'))->translatedFormat('F Y');

            throw new AccountingException("Tidak ada periode akuntansi untuk {$label}.");
        }

        if ($period->status === PeriodStatus::Closed) {
            $label = $date->locale(config('app.locale'))->translatedFormat('F Y');

            throw new AccountingException("Periode {$label} sudah ditutup.");
        }

        // 3. Every referenced account must exist and be postable.
        $accounts = Account::query()
            ->whereIn('id', collect($draft->lines)->map(fn (JournalDraftLine $line): int => $line->accountId)->unique())
            ->get()
            ->keyBy('id');

        foreach ($draft->lines as $line) {
            $account = $accounts->get($line->accountId);

            if ($account === null) {
                throw new AccountingException("Akun dengan ID {$line->accountId} tidak ditemukan.");
            }

            if ($account->is_header) {
                throw new AccountingException("Akun {$account->code} adalah akun grup dan tidak dapat diposting.");
            }

            if (! $account->is_active) {
                throw new AccountingException("Akun {$account->code} tidak aktif dan tidak dapat diposting.");
            }

            if ($line->debit < 0 || $line->credit < 0) {
                throw new AccountingException('Nilai debit/kredit tidak boleh negatif.');
            }

            if ($line->debit > 0 && $line->credit > 0) {
                throw new AccountingException('Baris jurnal tidak boleh memiliki debit dan kredit sekaligus.');
            }
        }

        // 4. A journal has at least two lines and sums balance to a positive total.
        if (count($draft->lines) < 2) {
            throw new AccountingException('Jurnal minimal memiliki dua baris.');
        }

        $totalDebit = collect($draft->lines)->sum(fn (JournalDraftLine $line): int => $line->debit);
        $totalCredit = collect($draft->lines)->sum(fn (JournalDraftLine $line): int => $line->credit);

        if ($totalDebit !== $totalCredit || $totalDebit <= 0) {
            throw new AccountingException('Total debit harus sama dengan total kredit.');
        }

        // Insert inside a transaction with the period row locked, so a
        // concurrent tutup buku cannot close the period between the check
        // above and the insert (doc 03 §3.1).
        return DB::transaction(function () use ($draft, $period, $accounts, $date): JournalEntry {
            $locked = AccountingPeriod::query()
                ->whereKey($period->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === PeriodStatus::Closed) {
                $label = $date->locale(config('app.locale'))->translatedFormat('F Y');

                throw new AccountingException("Periode {$label} sudah ditutup.");
            }

            $number = $this->sequences->next(
                key: 'journal',
                period: $locked->name,
                prefix: 'JE/'.$locked->name.'/',
            );

            $entry = JournalEntry::query()->create([
                'number' => $number,
                'entry_date' => $date,
                'accounting_period_id' => $locked->getKey(),
                'description' => $draft->description,
                'source' => $draft->source,
                'reference_type' => $draft->reference?->getMorphClass(),
                'reference_id' => $draft->reference?->getKey(),
                'status' => JournalStatus::Posted,
                'created_by' => $draft->userId,
            ]);

            $entry->lines()->createMany(
                collect($draft->lines)
                    ->map(fn (JournalDraftLine $line): array => [
                        'account_id' => $line->accountId,
                        // Fund dimension: explicit on the line, else the
                        // account's default fund (doc 03 §1/§4).
                        'fund_id' => $line->fundId ?? $accounts->get($line->accountId)->default_fund_id,
                        'debit' => $line->debit,
                        'credit' => $line->credit,
                        'memo' => $line->memo,
                    ])
                    ->all(),
            );

            return $entry;
        });
    }

    /**
     * Void a posted entry: create a mirrored reversal entry (linked via
     * voided_by_entry_id) and mark the original void. The reversal is
     * subject to the same period rules (doc 03 §3.3).
     */
    public function void(JournalEntry $entry, string $reason, ?string $label = null): JournalEntry
    {
        return DB::transaction(function () use ($entry, $reason, $label): JournalEntry {
            $entry = JournalEntry::query()
                ->whereKey($entry->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->status === JournalStatus::Void) {
                throw new AccountingException('Jurnal sudah dibatalkan.');
            }

            $reversal = $this->post(new JournalDraft(
                entryDate: $entry->entry_date->copy(),
                description: $label ?? "Pembatalan {$entry->number}: {$reason}",
                userId: $entry->created_by,
                source: $entry->source,
                reference: $entry->reference,
                lines: collect($entry->postedLineAttributes())
                    ->map(fn (array $line): JournalDraftLine => new JournalDraftLine(
                        accountId: $line['account_id'],
                        debit: $line['credit'],
                        credit: $line['debit'],
                        fundId: $line['fund_id'],
                        memo: $line['memo'],
                    ))
                    ->all(),
            ));

            $entry->forceFill([
                'status' => JournalStatus::Void,
                'voided_at' => now(),
                'voided_reason' => $reason,
                'voided_by_entry_id' => $reversal->getKey(),
            ])->save();

            return $reversal;
        });
    }
}
