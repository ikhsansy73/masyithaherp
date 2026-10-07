<?php

namespace App\Services\Accounting\Reports;

use App\Enums\NormalBalance;
use App\Exceptions\AccountingException;
use App\Models\Account;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Neraca Saldo (doc 03 §6.1): per postable account — opening, mutasi
 * debit/credit in range, ending. Totals assert Σ debit = Σ credit.
 */
final class TrialBalance
{
    /**
     * @return array{from: Carbon, to: Carbon, fund_id: ?int, rows: list<array<string, mixed>>, totals: array<string, int>}
     */
    public function generate(Carbon $from, Carbon $to, ?int $fundId = null): array
    {
        $opening = BalanceQuery::before($from, $fundId);
        $mutasi = BalanceQuery::sums($from, $to, $fundId);

        $accounts = Account::query()
            ->where('is_header', false)
            ->orderBy('code')
            ->get();

        $rows = [];

        foreach ($accounts as $account) {
            $open = $opening->get($account->getKey());
            $mut = $mutasi->get($account->getKey());

            $openingDebit = (int) ($open->debit_sum ?? 0);
            $openingCredit = (int) ($open->credit_sum ?? 0);
            $mutasiDebit = (int) ($mut->debit_sum ?? 0);
            $mutasiCredit = (int) ($mut->credit_sum ?? 0);

            if ($openingDebit === 0 && $openingCredit === 0 && $mutasiDebit === 0 && $mutasiCredit === 0) {
                continue;
            }

            $rows[] = [
                'account_id' => $account->getKey(),
                'code' => $account->code,
                'name' => $account->name,
                'opening_debit' => $openingDebit,
                'opening_credit' => $openingCredit,
                'mutasi_debit' => $mutasiDebit,
                'mutasi_credit' => $mutasiCredit,
                'ending_debit' => 0,
                'ending_credit' => 0,
            ];
        }

        $rows = $this->withEndingAndTotals($rows, $accounts);

        $totals = $rows['totals'];

        // The ΣD = ΣK assertion applies to the full statement only: a fund
        // filter slices individual lines, and entries may carry lines across
        // funds, so a fund-filtered neraca saldo is informational.
        if ($fundId === null) {
            if ($totals['mutasi_debit'] !== $totals['mutasi_credit']) {
                throw new AccountingException(
                    'Neraca saldo tidak seimbang: total mutasi debit '
                    .$totals['mutasi_debit'].' tidak sama dengan kredit '.$totals['mutasi_credit'].'.'
                );
            }

            if ($totals['ending_debit'] !== $totals['ending_credit']) {
                throw new AccountingException(
                    'Neraca saldo tidak seimbang: total akhir debit '
                    .$totals['ending_debit'].' tidak sama dengan kredit '.$totals['ending_credit'].'.'
                );
            }
        }

        return [
            'from' => $from,
            'to' => $to,
            'fund_id' => $fundId,
            'rows' => $rows['rows'],
            'totals' => $totals,
        ];
    }

    /**
     * Ending = opening ± mutasi, presented on the account's normal side
     * (abnormal balances flip to the opposite side). Totals accumulate
     * opening, mutasi, and ending per side.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  Collection<int, Account>  $accounts
     * @return array{rows: list<array<string, mixed>>, totals: array<string, int>}
     */
    private function withEndingAndTotals(array $rows, Collection $accounts): array
    {
        $debitNormalByAccount = $accounts->mapWithKeys(
            fn (Account $account): array => [$account->getKey() => $account->normal_balance === NormalBalance::Debit]
        );

        $totals = [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'mutasi_debit' => 0,
            'mutasi_credit' => 0,
            'ending_debit' => 0,
            'ending_credit' => 0,
        ];

        foreach ($rows as $index => $row) {
            $debitNormal = $debitNormalByAccount[$row['account_id']];

            $signedOpening = $debitNormal
                ? $row['opening_debit'] - $row['opening_credit']
                : $row['opening_credit'] - $row['opening_debit'];

            $signedMutasi = $row['mutasi_debit'] - $row['mutasi_credit'];

            $signedEnding = $signedOpening + ($debitNormal ? $signedMutasi : -$signedMutasi);

            // Positive signed ending presents on the account's normal side;
            // an abnormal balance flips to the opposite side.
            $endingDebit = $debitNormal ? max($signedEnding, 0) : max(-$signedEnding, 0);
            $endingCredit = $debitNormal ? max(-$signedEnding, 0) : max($signedEnding, 0);

            $rows[$index]['ending_debit'] = $endingDebit;
            $rows[$index]['ending_credit'] = $endingCredit;

            $totals['opening_debit'] += $row['opening_debit'];
            $totals['opening_credit'] += $row['opening_credit'];
            $totals['mutasi_debit'] += $row['mutasi_debit'];
            $totals['mutasi_credit'] += $row['mutasi_credit'];
            $totals['ending_debit'] += $endingDebit;
            $totals['ending_credit'] += $endingCredit;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }
}
