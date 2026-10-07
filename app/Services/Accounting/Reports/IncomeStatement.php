<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Laba Rugi (doc 03 §6.3): revenue (credit − debit) and expenses
 * (debit − credit) in range, grouped under their header accounts.
 * A fund filter turns this into the BOS realization report.
 */
final class IncomeStatement
{
    /**
     * @return array{from: Carbon, to: Carbon, fund_id: ?int, revenue: list<array<string, mixed>>, expenses: list<array<string, mixed>>, revenue_total: int, expenses_total: int, surplus: int}
     */
    public function generate(Carbon $from, Carbon $to, ?int $fundId = null): array
    {
        $accounts = Account::query()
            ->where('is_header', false)
            ->whereIn('type', [AccountType::Pendapatan, AccountType::Beban])
            ->orderBy('code')
            ->get();

        $sums = BalanceQuery::sums($from, $to, $fundId);

        $revenue = $this->groups(AccountType::Pendapatan, $accounts, $sums);
        $expenses = $this->groups(AccountType::Beban, $accounts, $sums);

        return [
            'from' => $from,
            'to' => $to,
            'fund_id' => $fundId,
            'revenue' => $revenue['groups'],
            'expenses' => $expenses['groups'],
            'revenue_total' => $revenue['total'],
            'expenses_total' => $expenses['total'],
            'surplus' => $revenue['total'] - $expenses['total'],
        ];
    }

    /**
     * Group postable accounts under their header (parent) account.
     *
     * @param  Collection<int, Account>  $accounts
     * @param  Collection<int, object>  $sums
     * @return array{groups: list<array{header: Account, lines: list<array{account: Account, amount: int}>, total: int}>, total: int}
     */
    private function groups(AccountType $type, Collection $accounts, Collection $sums): array
    {
        $headers = Account::query()
            ->where('type', $type)
            ->where('is_header', true)
            ->orderBy('code')
            ->get();

        foreach ($headers as $header) {
            $lines = [];

            foreach ($accounts->where('parent_id', $header->getKey()) as $account) {
                $sum = $sums->get($account->getKey());

                $amount = $sum === null ? 0 : (
                    $type === AccountType::Pendapatan
                        ? (int) $sum->credit_sum - (int) $sum->debit_sum
                        : (int) $sum->debit_sum - (int) $sum->credit_sum
                );

                if ($amount === 0) {
                    continue;
                }

                $lines[] = ['account' => $account, 'amount' => $amount];
            }

            if ($lines !== []) {
                $groups[] = ['header' => $header, 'lines' => $lines, 'total' => collect($lines)->sum('amount')];
            }
        }

        return [
            'groups' => $groups ?? [],
            'total' => collect($groups ?? [])->sum('total'),
        ];
    }
}
