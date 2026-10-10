<?php

namespace App\Services\Assets;

use App\Enums\JournalSource;
use App\Enums\MaintenanceType;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\CashAccount;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Perawatan & perbaikan aset (doc 07 §4). Cost is expensed to 5-1800,
 * never capitalized (rule #6 path) — cost 0 records a note-only entry
 * with no journal.
 */
class AssetMaintenanceService
{
    public function __construct(
        private readonly JournalPostingService $journals,
    ) {}

    public function record(
        Asset $asset,
        CarbonInterface $maintenanceDate,
        MaintenanceType $type,
        string $description,
        int $cost,
        ?int $cashAccountId,
        ?string $vendor,
        int $userId,
    ): AssetMaintenance {
        $cashAccount = null;

        if ($cost > 0) {
            $cashAccount = CashAccount::query()
                ->where('is_active', true)
                ->find($cashAccountId);

            if ($cashAccount === null) {
                throw new AccountingException('Kas/bank tidak aktif atau tidak ditemukan.');
            }
        }

        return DB::transaction(function () use ($asset, $maintenanceDate, $type, $description, $cost, $cashAccount, $vendor, $userId): AssetMaintenance {
            $entry = null;

            if ($cost > 0) {
                $entry = $this->journals->post(new JournalDraft(
                    entryDate: $maintenanceDate->copy()->startOfDay(),
                    description: 'Perawatan aset '.$asset->code.' — '.$description,
                    userId: $userId,
                    source: JournalSource::Otomatis,
                    lines: [
                        JournalDraftLine::debit(
                            (int) Account::query()->where('code', '5-1800')->value('id'),
                            $cost,
                            (int) $asset->fund_id,
                            $type->label().' '.$asset->name,
                        ),
                        JournalDraftLine::credit($cashAccount->account_id, $cost),
                    ],
                ));
            }

            $maintenance = AssetMaintenance::query()->create([
                'asset_id' => $asset->getKey(),
                'maintenance_date' => $maintenanceDate->copy()->startOfDay(),
                'type' => $type,
                'description' => $description,
                'cost' => $cost,
                'vendor' => $vendor,
                'journal_entry_id' => $entry?->getKey(),
            ]);

            activity()->performedOn($asset)->causedBy($userId)->log('Perawatan aset dicatat');

            return $maintenance;
        });
    }
}
