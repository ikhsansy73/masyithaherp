<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Enums\JournalSource;
use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monthly depreciation run (doc 07 §2). Straight line over the effective
 * useful life, capped at the remaining depreciable amount so NBV never
 * drops below salvage value. Rows are unique (asset, period) — reruns are
 * idempotent: assets already depreciated for the period are skipped, and
 * a run that produces no rows posts no journal entry.
 *
 * One JE per run (rule #13): Dr 5-1300 / Cr 1-2900, every line carrying
 * its asset's fund.
 */
class DepreciationService
{
    public function __construct(
        private readonly JournalPostingService $journals,
    ) {}

    /**
     * @return DepreciationRunResult rows inserted + the posted JE (#13)
     */
    public function run(AccountingPeriod $period, int $userId): DepreciationRunResult
    {
        if ($period->status === PeriodStatus::Closed) {
            throw new AccountingException("Periode {$period->name} sudah ditutup.");
        }

        return DB::transaction(function () use ($period, $userId): DepreciationRunResult {
            $assets = Asset::query()
                ->where('status', AssetStatus::Aktif)
                ->whereHas('category', fn ($query) => $query->where('is_depreciable', true))
                ->whereRaw("date_format(acquisition_date, '%Y-%m') <= ?", [$period->name])
                ->with('category')
                ->get();

            $rows = [];

            foreach ($assets as $asset) {
                $row = self::computeRow($asset, $period);

                if ($row !== null) {
                    $rows[] = $row;
                }
            }

            if ($rows === []) {
                return new DepreciationRunResult(count: 0, entry: null);
            }

            $entry = $this->journals->post(new JournalDraft(
                entryDate: Carbon::parse($period->ends_at)->startOfDay(),
                description: 'Penyusutan aset '.$period->name,
                userId: $userId,
                source: JournalSource::Otomatis,
                lines: collect($rows)->flatMap(fn (array $row): array => [
                    JournalDraftLine::debit(
                        Account::query()->where('code', '5-1300')->value('id'),
                        $row['amount'],
                        $row['fundId'],
                        'Penyusutan '.$row['assetCode'],
                    ),
                    JournalDraftLine::credit(
                        Account::query()->where('code', '1-2900')->value('id'),
                        $row['amount'],
                        $row['fundId'],
                        null,
                    ),
                ])->all(),
            ));

            foreach ($rows as $row) {
                AssetDepreciation::query()->create([
                    'asset_id' => $row['assetId'],
                    'accounting_period_id' => $period->getKey(),
                    'amount' => $row['amount'],
                    'accumulated_amount' => $row['accumulated'],
                    'journal_entry_id' => $entry->getKey(),
                ]);
            }

            activity()->withProperties(['period' => $period->name, 'assets' => count($rows)])->log('Penyusutan dijalankan');

            return new DepreciationRunResult(count: count($rows), entry: $entry);
        });
    }

    /**
     * One asset's monthly amount for this period, or null when the asset
     * is out of life for the period, already depreciated here, or fully
     * depreciated (NBV at salvage).
     *
     * @return array{assetId: int, assetCode: string, fundId: int, amount: int, accumulated: int}|null
     */
    private static function computeRow(Asset $asset, AccountingPeriod $period): ?array
    {
        if (AssetDepreciation::query()
            ->where('asset_id', $asset->getKey())
            ->where('accounting_period_id', $period->getKey())
            ->exists()) {
            return null;
        }

        $life = $asset->effectiveUsefulLifeMonths();

        if ($life === null || $life <= 0) {
            return null;
        }

        $base = $asset->acquisition_cost - $asset->salvage_value;

        if ($base <= 0) {
            return null;
        }

        $remaining = $asset->remainingDepreciableAmount();

        if ($remaining <= 0) {
            return null;
        }

        $monthly = (int) round($base / $life);

        return [
            'assetId' => (int) $asset->getKey(),
            'assetCode' => $asset->code,
            'fundId' => (int) $asset->fund_id,
            'amount' => min($monthly, $remaining),
            'accumulated' => $asset->accumulatedDepreciation() + min($monthly, $remaining),
        ];
    }
}
