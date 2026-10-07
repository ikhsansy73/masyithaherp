<?php

namespace App\Services\Accounting\Reports;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Exceptions\AccountingException;
use App\Models\Account;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Neraca (doc 03 §6.4): assets, liabilities, and equity as at a date,
 * with the exact A = K + E assertion (integer money — zero tolerance).
 */
final class BalanceSheet
{
    /**
     * @return array{as_of: Carbon, fund_id: ?int, assets: list<array{account: Account, amount: int}>, assets_total: int, liabilities: list<array{account: Account, amount: int}>, liabilities_total: int, equity: list<array{account: Account, amount: int}>, equity_total: int, surplus: int}
     */
    public function generate(Carbon $asOf, ?int $fundId = null): array
    {
        $sums = BalanceQuery::before($asOf->copy()->addDay(), $fundId);

        $accounts = Account::query()->where('is_header', false)->orderBy('code')->get();

        $assets = $this->section($sums, $accounts, AccountType::Aset);
        $liabilities = $this->section($sums, $accounts, AccountType::Kewajiban);
        $equity = $this->section($sums, $accounts, AccountType::Ekuitas);

        $nominal = $this->nominal($sums, $accounts);

        $equityTotal = $equity['total'] + $nominal;

        $difference = $assets['total'] - ($liabilities['total'] + $equityTotal);

        if ($difference !== 0) {
            throw new AccountingException(
                'Neraca tidak seimbang: total aset '.$assets['total']
                .' tidak sama dengan kewajiban + ekuitas '.($liabilities['total'] + $equityTotal).'.'
            );
        }

        return [
            'as_of' => $asOf,
            'fund_id' => $fundId,
            'assets' => $assets['rows'],
            'assets_total' => $assets['total'],
            'liabilities' => $liabilities['rows'],
            'liabilities_total' => $liabilities['total'],
            'equity' => $equity['rows'],
            'equity_total' => $equityTotal,
            'surplus' => $nominal,
        ];
    }

    /**
     * Postable rows of one section, signed to the section's normal side.
     *
     * @param  Collection<int, object>  $sums
     * @param  Collection<int, Account>  $accounts
     * @return array{rows: list<array{account: Account, amount: int}>, total: int}
     */
    private function section(Collection $sums, Collection $accounts, AccountType $type): array
    {
        $rows = [];

        foreach ($accounts->where('type', $type) as $account) {
            $sum = $sums->get($account->getKey());

            $debit = (int) ($sum->debit_sum ?? 0);
            $credit = (int) ($sum->credit_sum ?? 0);

            $amount = $account->normal_balance === NormalBalance::Debit
                ? $debit - $credit
                : $credit - $debit;

            if ($amount === 0) {
                continue;
            }

            $rows[] = ['account' => $account, 'amount' => $amount];
        }

        return ['rows' => $rows, 'total' => collect($rows)->sum('amount')];
    }

    /**
     * Current surplus: revenue (credit − debit) minus expenses (debit −
     * credit) over ALL entries. With closing entries folded into 3-1200
     * (tutup buku), this plus the equity accounts always reconstructs the
     * retained result — so A = K + E holds whatever the closing state is.
     *
     * @param  Collection<int, object>  $sums
     * @param  Collection<int, Account>  $accounts
     */
    private function nominal(Collection $sums, Collection $accounts): int
    {
        $revenue = 0;
        $expenses = 0;

        foreach ($accounts->where('type', AccountType::Pendapatan) as $account) {
            $sum = $sums->get($account->getKey());

            if ($sum !== null) {
                $revenue += (int) $sum->credit_sum - (int) $sum->debit_sum;
            }
        }

        foreach ($accounts->where('type', AccountType::Beban) as $account) {
            $sum = $sums->get($account->getKey());

            if ($sum !== null) {
                $expenses += (int) $sum->debit_sum - (int) $sum->credit_sum;
            }
        }

        return $revenue - $expenses;
    }
}
