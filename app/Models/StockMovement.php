<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'movement_date',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'purpose',
        'requester_id',
        'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'type' => StockMovementType::class,
            'quantity' => 'float',
            'unit_cost' => 'integer',
            'total_cost' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * JE #7 (masuk) / #8 (keluar).
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
