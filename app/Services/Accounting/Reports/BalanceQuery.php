<?php

namespace App\Services\Accounting\Reports;

use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared per-account debit/credit aggregation for the statement services.
 * Sums include every entry regardless of status: a void entry and its
 * mirrored reversal net to zero, so the ledger total must count both.
 */
final class BalanceQuery
{
    /**
     * Sum of debit/credit per account within the date range (inclusive).
     *
     * @return Collection<int, object> keyed by account_id
     */
    public static function sums(Carbon $from, Carbon $to, ?int $fundId = null): Collection
    {
        return self::query($fundId)
            ->where('journal_entries.entry_date', '>=', $from->toDateString())
            ->where('journal_entries.entry_date', '<=', $to->toDateString())
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Sum of debit/credit per account before the given date (opening).
     *
     * @return Collection<int, object> keyed by account_id
     */
    public static function before(Carbon $date, ?int $fundId = null): Collection
    {
        return self::query($fundId)
            ->where('journal_entries.entry_date', '<', $date->toDateString())
            ->get()
            ->keyBy('account_id');
    }

    private static function query(?int $fundId): Builder
    {
        return JournalLine::query()
            ->selectRaw('journal_lines.account_id, COALESCE(SUM(journal_lines.debit), 0) as debit_sum, COALESCE(SUM(journal_lines.credit), 0) as credit_sum')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->when($fundId !== null, fn (Builder $q) => $q->where('journal_lines.fund_id', $fundId))
            ->groupBy('journal_lines.account_id');
    }
}
