<?php

namespace App\Services\Inventory;

use App\Enums\JournalSource;
use App\Enums\StockMovementType;
use App\Exceptions\AccountingException;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Barang habis pakai / ATK (doc 07 §6) with weighted-average costing:
 * - receive (rule #7): Dr 1-1400 / Cr Kas-Bank, then avg_cost is
 *   recomputed over the combined stock;
 * - issue (rule #8): Dr 5-1400 (or 5-1600 kegiatan) / Cr 1-1400 at the
 *   current average cost.
 * Invariant: stock × avg_cost ≡ 1-1400 balance.
 */
class InventoryService
{
    public function __construct(
        private readonly JournalPostingService $journals,
    ) {}

    public function receive(
        InventoryItem $item,
        CarbonInterface $movementDate,
        float $quantity,
        int $unitCost,
        int $cashAccountId,
        int $userId,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new AccountingException('Jumlah masuk harus lebih besar dari nol.');
        }

        if ($unitCost <= 0) {
            throw new AccountingException('Harga per unit wajib lebih besar dari nol.');
        }

        $cashAccount = CashAccount::query()
            ->where('is_active', true)
            ->find($cashAccountId);

        if ($cashAccount === null) {
            throw new AccountingException('Kas/bank tidak aktif atau tidak ditemukan.');
        }

        return DB::transaction(function () use ($item, $movementDate, $quantity, $unitCost, $cashAccountId, $userId, $notes): StockMovement {
            $totalCost = (int) round($quantity * $unitCost);

            $entry = $this->journals->post(new JournalDraft(
                entryDate: $movementDate->copy()->startOfDay(),
                description: 'Pembelian ATK '.$item->name.' ('.$quantity.' '.$item->unit->value.')',
                userId: $userId,
                source: JournalSource::Otomatis,
                lines: [
                    JournalDraftLine::debit(
                        (int) Account::query()->where('code', '1-1400')->value('id'),
                        $totalCost,
                        null,
                        $notes,
                    ),
                    JournalDraftLine::credit(
                        (int) CashAccount::query()->find($cashAccountId)->account_id,
                        $totalCost,
                    ),
                ],
            ));

            $oldStock = (float) $item->current_stock;
            $newStock = $oldStock + $quantity;
            $avgCost = $newStock > 0
                ? (int) round((($oldStock * $item->avg_cost) + $totalCost) / $newStock)
                : $unitCost;

            $item->forceFill([
                'current_stock' => $newStock,
                'avg_cost' => $avgCost,
            ])->save();

            $movement = StockMovement::query()->create([
                'inventory_item_id' => $item->getKey(),
                'movement_date' => $movementDate->copy()->startOfDay(),
                'type' => StockMovementType::Masuk,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'purpose' => $notes,
                'journal_entry_id' => $entry->getKey(),
            ]);

            activity()->causedBy($userId)->performedOn($item)->log(
                "Stok masuk {$quantity} {$item->unit->value} @ Rp ".number_format($unitCost, 0, ',', '.'),
            );

            return $movement;
        });
    }

    public function issue(
        InventoryItem $item,
        CarbonInterface $movementDate,
        float $quantity,
        int $expenseAccountId,
        int $userId,
        ?string $purpose = null,
        ?int $requesterId = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new AccountingException('Jumlah keluar harus lebih besar dari nol.');
        }

        if ($quantity > (float) $item->current_stock) {
            throw new AccountingException(sprintf(
                'Stok %s tidak cukup (tersedia %.2f %s).',
                $item->name,
                $item->current_stock,
                $item->unit->value,
            ));
        }

        return DB::transaction(function () use ($item, $movementDate, $quantity, $expenseAccountId, $userId, $purpose, $requesterId): StockMovement {
            $unitCost = (int) $item->avg_cost;
            $totalCost = (int) round($quantity * $unitCost);

            $entry = $this->journals->post(new JournalDraft(
                entryDate: $movementDate->copy()->startOfDay(),
                description: 'Pemakaian ATK '.$item->name.' ('.$quantity.' '.$item->unit->value.')',
                userId: $userId,
                source: JournalSource::Otomatis,
                lines: [
                    JournalDraftLine::debit(
                        $expenseAccountId,
                        $totalCost,
                        null,
                        $purpose,
                    ),
                    JournalDraftLine::credit(
                        (int) Account::query()->where('code', '1-1400')->value('id'),
                        $totalCost,
                        null,
                        $purpose,
                    ),
                ],
            ));

            $item->forceFill([
                'current_stock' => (float) $item->current_stock - $quantity,
            ])->save();

            $movement = StockMovement::query()->create([
                'inventory_item_id' => $item->getKey(),
                'movement_date' => $movementDate->copy()->startOfDay(),
                'type' => StockMovementType::Keluar,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'purpose' => $purpose,
                'requester_id' => $requesterId,
                'journal_entry_id' => $entry->getKey(),
            ]);

            activity()->causedBy($userId)->performedOn($item)->log(
                "Stok keluar {$quantity} {$item->unit->value} @ Rp ".number_format($unitCost, 0, ',', '.'),
            );

            return $movement;
        });
    }
}
