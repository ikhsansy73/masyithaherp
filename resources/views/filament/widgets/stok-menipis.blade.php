<x-filament-widgets::widget>
    <h3 style="font-weight: 600; font-size: var(--text-sm); margin: 0 0 0.75rem">
        Stok ATK Menipis
    </h3>
    <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
        <thead>
            <tr>
                <th scope="col" class="fi-ta-header-cell">Item</th>
                <th scope="col" class="fi-ta-header-cell" style="text-align: right">Stok</th>
                <th scope="col" class="fi-ta-header-cell" style="text-align: right">Min.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->lowStockItems() as $item)
                <tr class="fi-ta-row">
                    <td class="fi-ta-cell" style="padding-block: 0.6rem">
                        <div class="fi-ta-cell-content" style="font-weight: 500">
                            {{ $item->name }}
                            <x-filament::badge color="gray">
                                {{ $item->code }}
                            </x-filament::badge>
                        </div>
                    </td>
                    <td class="fi-ta-cell" style="text-align: right">
                        <div class="fi-ta-cell-content" style="color: var(--danger-600); font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap">
                            {{ number_format($item->current_stock, 2, ',', '.') }} {{ $item->unit->value }}
                        </div>
                    </td>
                    <td class="fi-ta-cell" style="text-align: right">
                        <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                            {{ number_format($item->min_stock, 2, ',', '.') }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="fi-ta-row">
                    <td class="fi-ta-cell" colspan="3" style="text-align: center; padding-block: 1.5rem">
                        <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                            Semua stok aman.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-filament-widgets::widget>
