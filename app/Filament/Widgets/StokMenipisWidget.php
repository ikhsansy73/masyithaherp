<?php

namespace App\Filament\Widgets;

use App\Models\InventoryItem;
use Filament\Widgets\Widget;

class StokMenipisWidget extends Widget
{
    protected string $view = 'filament.widgets.stok-menipis';

    protected int|string|array $columnSpan = 1;

    public function canAccess(): bool
    {
        return auth()->user()?->can('assets.asset.update') ?? false;
    }

    /**
     * @return \Illuminate\Support\Collection<int, InventoryItem>
     */
    public function lowStockItems(): \Illuminate\Support\Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock')
            ->limit(8)
            ->get();
    }
}
