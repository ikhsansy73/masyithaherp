<?php

namespace App\Models;

use App\Enums\InventoryUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'current_stock',
        'min_stock',
        'avg_cost',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit' => InventoryUnit::class,
            'current_stock' => 'float',
            'min_stock' => 'float',
            'avg_cost' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Nilai persediaan buku besar: stok × harga rata-rata.
     */
    public function stockValue(): int
    {
        return (int) round($this->current_stock * $this->avg_cost);
    }

    /**
     * Stok berada pada atau di bawah ambang minimum (widget stok menipis).
     */
    public function isLowStock(): bool
    {
        return $this->min_stock > 0 && $this->current_stock <= $this->min_stock;
    }
}
