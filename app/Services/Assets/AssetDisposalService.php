<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Enums\DisposalMethod;
use App\Enums\JournalSource;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetOpnameItem;
use App\Models\CashAccount;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Penghapusan aset (doc 07 §3). NBV = cost − accumulated depreciation;
 * one JE per disposal:
 * - with proceeds / dijual (rule #14): Dr kas (proceeds) + Dr 1-2900
 *   (accumulated) + Dr 5-1950 (loss) / Cr 1-21xx (cost) + Cr 4-1900 (gain);
 * - without proceeds / dihapuskan-hilang (rule #15): Dr 1-2900 +
 *   Dr 5-1950 (NBV) / Cr 1-21xx.
 * `hilang` requires a supporting opname finding (found = false).
 */
class AssetDisposalService
{
    public function __construct(
        private readonly JournalPostingService $journals,
    ) {}

    public function dispose(
        Asset $asset,
        CarbonInterface $disposalDate,
        DisposalMethod $method,
        ?int $proceeds,
        ?int $cashAccountId,
        int $userId,
    ): Asset {
        if ($asset->status !== AssetStatus::Aktif) {
            throw new AccountingException('Hanya aset berstatus aktif yang dapat dihapuskan.');
        }

        if ($method === DisposalMethod::Hilang) {
            $hasFinding = AssetOpnameItem::query()
                ->where('asset_id', $asset->getKey())
                ->where('found', false)
                ->exists();

            if (! $hasFinding) {
                throw new AccountingException('Penghapusan dengan status hilang wajib memiliki temuan opname (aset tidak ditemukan).');
            }
        }

        $proceeds = $proceeds ?? 0;

        if ($method->hasProceeds()) {
            if ($proceeds <= 0) {
                throw new AccountingException('Hasil penjualan aset wajib lebih besar dari nol.');
            }

            $cashAccount = CashAccount::query()
                ->where('is_active', true)
                ->find($cashAccountId);

            if ($cashAccount === null) {
                throw new AccountingException('Kas/bank penerima hasil penjualan tidak aktif atau tidak ditemukan.');
            }
        }

        return DB::transaction(function () use ($asset, $disposalDate, $method, $proceeds, $cashAccountId, $userId): Asset {
            $accumulated = $asset->accumulatedDepreciation();
            $nbv = $asset->netBookValue();
            $cost = (int) $asset->acquisition_cost;
            $fundId = (int) $asset->fund_id;
            $assetAccountId = $asset->category->asset_account_id;

            $lines = [];

            if ($method->hasProceeds()) {
                $lines[] = JournalDraftLine::debit(
                    (int) CashAccount::query()->find($cashAccountId)->account_id,
                    $proceeds,
                );

                $gain = $proceeds - $nbv;

                if ($gain > 0) {
                    $lines[] = JournalDraftLine::credit(
                        (int) Account::query()->where('code', '4-1900')->value('id'),
                        $gain,
                        $fundId,
                        'Kelebihan hasil penjualan atas nilai buku',
                    );
                } elseif ($gain < 0) {
                    $lines[] = JournalDraftLine::debit(
                        (int) Account::query()->where('code', '5-1950')->value('id'),
                        $nbv - $proceeds,
                        $fundId,
                        'Rugi penghapusan aset',
                    );
                }
            } elseif ($nbv > 0) {
                $lines[] = JournalDraftLine::debit(
                    (int) Account::query()->where('code', '5-1950')->value('id'),
                    $nbv,
                    $fundId,
                    'Beban penghapusan aset',
                );
            }

            if ($accumulated > 0) {
                $lines[] = JournalDraftLine::debit(
                    (int) Account::query()->where('code', '1-2900')->value('id'),
                    $accumulated,
                    $fundId,
                    'Akumulasi penyusutan dihapus',
                );
            }
            $lines[] = JournalDraftLine::credit($assetAccountId, $cost, $fundId, 'Hapus penetapan biaya perolehan');

            $entry = $this->journals->post(new JournalDraft(
                entryDate: $disposalDate->copy()->startOfDay(),
                description: 'Penghapusan aset '.$asset->code.' — '.$asset->name,
                userId: $userId,
                source: JournalSource::Otomatis,
                lines: $lines,
            ));

            $asset->forceFill([
                'status' => self::statusFor($method),
                'disposal_date' => $disposalDate->copy()->startOfDay(),
                'disposal_method' => $method,
                'disposal_proceeds' => $method->hasProceeds() ? $proceeds : 0,
                'disposal_journal_entry_id' => $entry->getKey(),
            ])->save();

            activity()->performedOn($asset)->causedBy($userId)->log('Aset dihapuskan');

            return $asset->refresh();
        });
    }

    private static function statusFor(DisposalMethod $method): AssetStatus
    {
        return match ($method) {
            DisposalMethod::Dijual => AssetStatus::Dijual,
            DisposalMethod::Dihapuskan => AssetStatus::Dihapuskan,
            DisposalMethod::Hilang => AssetStatus::Hilang,
        };
    }
}
