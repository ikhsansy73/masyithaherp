<?php

namespace App\Services\Accounting\Reports;

use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;

/**
 * Buku Besar (doc 03 §6.2): journal lines per account in range with a
 * running balance; every row carries the parent entry for drill-down.
 */
final class GeneralLedger
{
    /**
     * @return array{account: Account, from: Carbon, to: Carbon, fund_id: ?int, opening: int, lines: list<array<string, mixed>>, closing: int}
     */
    public function generate(Account $account, Carbon $from, Carbon $to, ?int $fundId = null): array
    {
        $openingSums = BalanceQuery::before($from, $fundId)->get($account->getKey());

        $opening = $openingSums === null
            ? 0
            : ($account->normal_balance === NormalBalance::Debit
                ? $openingSums->debit_sum - $openingSums->credit_sum
                : $openingSums->credit_sum - $openingSums->debit_sum);

        $lines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.account_id', $account->id)
            ->where('journal_entries.entry_date', '>=', $from->toDateString())
            ->where('journal_entries.entry_date', '<=', $to->toDateString())
            ->when($fundId !== null, fn ($q) => $q->where('journal_lines.fund_id', $fundId))
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.number')
            ->orderBy('journal_lines.id')
            ->get();

        $running = (int) $opening;
        $rows = [];

        foreach ($lines as $line) {
            $running += $account->normal_balance === NormalBalance::Debit
                ? $line->debit - $line->credit
                : $line->credit - $line->debit;

            $rows[] = [
                'journal_entry_id' => $line->journal_entry_id,
                'number' => $line->number, 'entry_date' => $line->entry_date,
                'description' => $line->description,
                'memo' => $line->memo,
                'debit' => (int) $line->debit,
                'credit' => (int) $line->credit,
                'fund_id' => $line->fund_id,
                'running' => $running,
            ];
        }

        return [
            'account' => $account,
            'from' => $from,
            'to' => $to,
            'fund_id' => $fundId,
            'opening' => (int) $opening,
            'lines' => $rows,
            'closing' => $running,
        ];
    }
}
