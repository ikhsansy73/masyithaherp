<?php

namespace App\Services\Accounting\Reports;

use App\Enums\CashFlowCategory;
use App\Models\Account;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;

/**
 * Arus Kas (doc 03 §6.5), direct method: each entry's net cash movement is
 * classified by the sibling lines' cash_flow_category (operasi / investasi /
 * pendanaan). A fourth non_kas bucket receives transfers between cash
 * accounts and other non-cash siblings, so the buckets always sum to the
 * net cash movement — the statement ties by construction.
 */
final class CashFlow
{
    /**
     * @return array{from: Carbon, to: Carbon, fund_id: ?int, accounts: array<string, array{opening: int, closing: int}>, operasi: array{in: int, out: int, net: int}, investasi: array{in: int, out: int, net: int}, pendanaan: array{in: int, out: int, net: int}, non_kas: array{net: int}, opening: int, closing: int}
     */
    public function generate(Carbon $from, Carbon $to, ?int $fundId = null): array
    {
        $cashAccounts = Account::query()
            ->whereIn('code', Account::CASH_ACCOUNT_CODES)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        $openingSums = BalanceQuery::before($from, $fundId);
        $throughSums = BalanceQuery::before($to->copy()->addDay(), $fundId);

        $opening = 0;
        $closing = 0;
        $accounts = [];

        foreach ($cashAccounts as $cashAccount) {
            $openingSum = $openingSums->get($cashAccount->getKey());
            $throughSum = $throughSums->get($cashAccount->getKey());

            $openingAmount = $openingSum === null
                ? 0
                : (int) $openingSum->debit_sum - (int) $openingSum->credit_sum;

            $closingAmount = $throughSum === null
                ? 0
                : (int) $throughSum->debit_sum - (int) $throughSum->credit_sum;

            $accounts[$cashAccount->code] = ['opening' => $openingAmount, 'closing' => $closingAmount];

            $opening += $openingAmount;
            $closing += $closingAmount;
        }

        $buckets = $this->buckets($cashAccounts, $from, $to, $fundId);

        return [
            'from' => $from,
            'to' => $to,
            'fund_id' => $fundId,
            'accounts' => $accounts,
            'operasi' => $buckets['operasi'],
            'investasi' => $buckets['investasi'],
            'pendanaan' => $buckets['pendanaan'],
            'non_kas' => $buckets['non_kas'],
            'opening' => $opening,
            'closing' => $closing,
        ];
    }

    /**
     * Per-entry cash movement distributed into the sibling lines'
     * cash_flow_category buckets. Since every entry balances, the buckets
     * sum to the net cash movement exactly.
     *
     * @param  \Illuminate\Support\Collection<int, Account>  $cashAccounts
     * @return array{operasi: array{in: int, out: int, net: int}, investasi: array{in: int, out: int, net: int}, pendanaan: array{in: int, out: int, net: int}, non_kas: array{net: int}}
     */
    private function buckets($cashAccounts, Carbon $from, Carbon $to, ?int $fundId): array
    {
        $buckets = [
            'operasi' => ['in' => 0, 'out' => 0, 'net' => 0],
            'investasi' => ['in' => 0, 'out' => 0, 'net' => 0],
            'pendanaan' => ['in' => 0, 'out' => 0, 'net' => 0],
            'non_kas' => ['net' => 0],
        ];

        $cashLineIds = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('journal_lines.account_id', $cashAccounts->keys())
            ->where('journal_entries.entry_date', '>=', $from->toDateString())
            ->where('journal_entries.entry_date', '<=', $to->toDateString())
            ->when($fundId !== null, fn ($q) => $q->where('journal_lines.fund_id', $fundId))
            ->pluck('journal_lines.id');

        $cashLines = JournalLine::query()
            ->whereKey($cashLineIds)
            ->with('account')
            ->get();

        $lineByEntry = $cashLines->groupBy('journal_entry_id');

        foreach ($lineByEntry as $entryLines) {
            $siblings = JournalLine::query()
                ->where('journal_entry_id', $entryLines->first()->journal_entry_id)
                ->whereKeyNot($entryLines->modelKeys())
                ->whereNotIn('account_id', $cashAccounts->keys())
                ->with('account')
                ->get();

            $amountsByCategory = [];

            foreach ($siblings as $sibling) {
                $category = $sibling->account->cash_flow_category ?? CashFlowCategory::NonKas;

                $amountsByCategory[$category->value] = ($amountsByCategory[$category->value] ?? 0)
                    + (int) $sibling->credit - (int) $sibling->debit;
            }

            foreach ($amountsByCategory as $categoryKey => $amount) {
                if ($amount === 0) {
                    continue;
                }

                if ($categoryKey === 'non_kas') {
                    $buckets['non_kas']['net'] += $amount;

                    continue;
                }

                $buckets[$categoryKey]['in'] += $amount > 0 ? $amount : 0;
                $buckets[$categoryKey]['out'] += $amount < 0 ? -$amount : 0;
                $buckets[$categoryKey]['net'] += $amount;
            }
        }

        return $buckets;
    }
}
