<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Enums\JournalSource;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\CashAccount;
use App\Models\Fund;
use App\Models\Location;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use App\Services\Shared\DocumentSequenceService;
use Illuminate\Support\Facades\DB;

/**
 * Asset registry writes (doc 07 §1). Acquisition posts JE #12 through the
 * single journal write path; "Duplikat aset" clones a unit row (its own
 * JE #12) for bulk purchases. One row = one physical unit.
 */
class AssetService
{
    public function __construct(
        private readonly JournalPostingService $journals,
        private readonly DocumentSequenceService $sequences,
    ) {}

    /**
     * Entry point shared by acquire() and duplicate(): validates and posts
     * JE #12, then inserts the unit row.
     */
    private function persist(AcquisitionData $data, ?string $descriptionSuffix = null): Asset
    {
        $category = AssetCategory::query()->findOrFail($data->categoryId);
        $fund = Fund::query()->findOrFail($data->fundId);
        $location = Location::query()->findOrFail($data->locationId);

        if ($data->cost <= 0) {
            throw new AccountingException('Biaya perolehan aset harus lebih besar dari nol.');
        }

        $cashAccount = null;

        if ($data->paymentMode === 'kas_bank') {
            $cashAccount = CashAccount::query()
                ->where('is_active', true)
                ->find($data->cashAccountId);

            if ($cashAccount === null) {
                throw new AccountingException('Kas/bank tidak aktif atau tidak ditemukan.');
            }
        }

        return DB::transaction(function () use ($data, $category, $fund, $location, $cashAccount, $descriptionSuffix): Asset {
            $creditAccountId = $data->paymentMode === 'utang'
                ? self::utangVendorAccountId()
                : $cashAccount->account_id;

            $label = $data->name.($descriptionSuffix !== null ? ' — '.$descriptionSuffix : '');

            $entry = $this->journals->post(new JournalDraft(
                entryDate: $data->acquisitionDate->copy()->startOfDay(),
                description: 'Akuisisi aset '.$label,
                userId: $data->userId,
                source: JournalSource::Otomatis,
                lines: [
                    JournalDraftLine::debit(
                        $category->asset_account_id,
                        $data->cost,
                        $fund->getKey(),
                        'Perolehan '.$label,
                    ),
                    JournalDraftLine::credit($creditAccountId, $data->cost),
                ],
            ));

            $asset = Asset::query()->create([
                'code' => $this->sequences->next(
                    key: 'asset',
                    period: $data->acquisitionDate->format('Y'),
                    prefix: 'INV-Aset/'.$data->acquisitionDate->format('Y').'/',
                ),
                'name' => $data->name,
                'asset_category_id' => $category->getKey(),
                'acquisition_date' => $data->acquisitionDate->copy()->startOfDay(),
                'acquisition_cost' => $data->cost,
                'fund_id' => $fund->getKey(),
                'funding_source' => $data->fundingSource,
                'brand_model' => $data->brandModel,
                'serial_no' => $data->serialNo,
                'condition' => $data->condition,
                'location_id' => $location->getKey(),
                'custodian_id' => $data->custodianId,
                'status' => AssetStatus::Aktif,
                'useful_life_months' => $data->usefulLifeMonths,
                'salvage_value' => $data->salvageValue ?? 0,
                'notes' => $data->notes,
                'journal_entry_id' => $entry->getKey(),
            ]);

            activity()->performedOn($asset)->causedBy($data->userId)->log('Aset terdaftar');

            return $asset;
        });
    }

    /**
     * Register a unit and post JE #12: Dr 1-21xx (fund = asset fund) /
     * Cr kas/bank — or Cr 2-1400 Utang Vendor for credit purchases.
     */
    public function acquire(AcquisitionData $data): Asset
    {
        return $this->persist($data);
    }

    /**
     * "Duplikat aset": clone a unit row with a fresh inventory code for
     * bulk purchases — the clone is a second physical unit, so it posts
     * its own JE #12 (doc 07 §1).
     */
    public function duplicate(Asset $asset, int $userId): Asset
    {
        $cashAccount = null;

        if ($asset->status !== AssetStatus::Aktif) {
            throw new AccountingException('Hanya aset berstatus aktif yang dapat diduplikat.');
        }

        if ($asset->journal_entry_id === null) {
            throw new AccountingException('Aset sumber belum memiliki jurnal akuisisi.');
        }

        $cashLine = $asset->journalEntry->lines
            ->firstWhere(fn ($line): bool => $line->debit === 0);

        $cashAccount = CashAccount::query()
            ->where('account_id', $cashLine?->account_id)
            ->where('is_active', true)
            ->first();

        $data = new AcquisitionData(
            categoryId: (int) $asset->asset_category_id,
            name: $asset->name,
            acquisitionDate: $asset->acquisition_date,
            cost: $asset->acquisition_cost,
            fundId: (int) $asset->fund_id,
            fundingSource: $asset->funding_source,
            condition: $asset->condition,
            locationId: (int) $asset->location_id,
            custodianId: $asset->custodian_id !== null ? (int) $asset->custodian_id : null,
            brandModel: $asset->brand_model,
            serialNo: $asset->serial_no,
            usefulLifeMonths: $asset->useful_life_months,
            salvageValue: $asset->salvage_value,
            paymentMode: $cashAccount !== null ? 'kas_bank' : 'utang',
            cashAccountId: $cashAccount?->getKey(),
            notes: $asset->notes,
            userId: $userId,
        );

        return $this->persist($data, 'duplikat '.$asset->code);
    }

    private static function utangVendorAccountId(): int
    {
        return (int) Account::query()->where('code', '2-1400')->value('id');
    }
}
