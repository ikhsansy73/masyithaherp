<x-filament-widgets::widget>
    <h3 class="text-sm font-semibold mb-2 px-2">Stok ATK Menipis</h3>
    <table class="fi-ta-table w-full text-sm">
        <thead>
            <tr>
                <th class="px-3 py-2 text-start">Item</th>
                <th class="px-3 py-2 text-end">Stok</th>
                <th class="px-3 py-2 text-end">Min.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->lowStockItems() as $item)
            <tr>
                <td class="px-3 py-2">{{ $item->name }} <span class="fi-badge fi-badge-color-danger">{{ $item->code }}</span></td>
                <td class="px-3 py-2 text-end text-danger-600">{{ number_format($item->current_stock, 2, ',', '.') }} {{ $item->unit->value }}</td>
                <td class="px-3 py-2 text-end">{{ number_format($item->min_stock, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-3 py-2 text-gray-500">Semua stok aman.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-filament-widgets::widget>
