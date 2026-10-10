<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Enums\DisposalMethod;
use App\Models\Asset;
use App\Models\AssetOpname;
use App\Models\AssetOpnameItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Opname (stocktaking) — doc 07 §5. A run snapshots every aktif asset
 * (optionally per location) into asset_opname_items; finalisasi applies
 * confirmed condition updates and, for items checked "not found", posts
 * the hilang disposal JE through AssetDisposalService (rule #15).
 */
class AssetOpnameService
{
    public function __construct(
        private readonly AssetDisposalService $disposals,
    ) {}

    /**
     * Create a run and snapshot all aktif assets as items (found = true,
     * condition = current asset condition) for per-location check-off.
     *
     * @return array{opname: AssetOpname, itemCount: int}
     */
    public function create(
        string $name,
        CarbonInterface $opnameDate,
        int $userId,
        ?int $locationId = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($name, $opnameDate, $userId, $locationId, $notes): array {
            $opname = AssetOpname::query()->create([
                'name' => $name,
                'opname_date' => $opnameDate->copy()->startOfDay(),
                'conducted_by' => $userId,
                'notes' => $notes,
            ]);

            $assets = Asset::query()
                ->where('status', AssetStatus::Aktif)
                ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
                ->orderBy('code')
                ->get();

            foreach ($assets as $asset) {
                AssetOpnameItem::query()->create([
                    'asset_opname_id' => $opname->getKey(),
                    'asset_id' => $asset->getKey(),
                    'found' => true,
                    'condition' => $asset->condition,
                    'notes' => null,
                ]);
            }

            activity()->performedOn($opname)->causedBy($userId)->log('Opname aset dibuat');

            return ['opname' => $opname, 'itemCount' => $assets->count()];
        });
    }

    /**
     * Apply check-off results: condition updates for found items and hilang
     * disposal JEs for missing ones. Assets no longer aktif (already
     * disposed, e.g. by an earlier finalisasi) are skipped, so reruns are
     * safe no-ops for those items.
     *
     * @return array{conditionsUpdated: int, missingDisposed: int}
     */
    public function finalize(AssetOpname $opname, int $userId): array
    {
        return DB::transaction(function () use ($opname, $userId): array {
            $conditionsUpdated = 0;
            $missingDisposed = 0;

            $items = $opname->items()->with('asset')->get();

            foreach ($items as $item) {
                $asset = $item->asset;

                if ($asset->status !== AssetStatus::Aktif) {
                    continue;
                }

                if ($item->found === false) {
                    $this->disposals->dispose(
                        $asset,
                        $opname->opname_date,
                        DisposalMethod::Hilang,
                        null,
                        null,
                        $userId,
                    );
                    $missingDisposed++;

                    continue;
                }

                if ($item->condition !== null && $item->condition !== $asset->condition) {
                    $asset->forceFill(['condition' => $item->condition])->save();
                    $conditionsUpdated++;
                }
            }

            activity()->performedOn($opname)->causedBy($userId)->log(
                "Opname difinalisasi: {$conditionsUpdated} kondisi diperbarui, {$missingDisposed} aset hilang dihapuskan",
            );

            return ['conditionsUpdated' => $conditionsUpdated, 'missingDisposed' => $missingDisposed];
        });
    }
}
