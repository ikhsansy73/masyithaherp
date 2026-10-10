<x-filament-widgets::widget>
    <h3 style="font-weight: 600; font-size: var(--text-sm); margin: 0 0 0.75rem">
        Tunggakan Teratas
    </h3>
    <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
        <thead>
            <tr>
                <th scope="col" class="fi-ta-header-cell" style="width: 3rem">#</th>
                <th scope="col" class="fi-ta-header-cell">Siswa</th>
                <th scope="col" class="fi-ta-header-cell" style="width: 7rem">Kelas</th>
                <th scope="col" class="fi-ta-header-cell" style="text-align: right">Tunggakan</th>
                <th scope="col" class="fi-ta-header-cell" style="text-align: right">Telat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->topArrears() as $row)
                <tr class="fi-ta-row fi-striped">
                    <td class="fi-ta-cell" style="padding-block: 0.6rem">
                        <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                            {{ $loop->iteration }}
                        </div>
                    </td>
                    <td class="fi-ta-cell">
                        <div class="fi-ta-cell-content" style="font-weight: 500">
                            {{ $row['student']->full_name }}
                        </div>
                    </td>
                    <td class="fi-ta-cell">
                        <div class="fi-ta-cell-content">
                            {{ $row['classroom'] ?? '-' }}
                        </div>
                    </td>
                    <td class="fi-ta-cell" style="text-align: right">
                        <div class="fi-ta-cell-content" style="font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap">
                            Rp {{ number_format($row['total'], 0, ',', '.') }}
                        </div>
                    </td>
                    <td class="fi-ta-cell" style="text-align: right">
                        <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap; {{ $row['days_overdue'] > 90 ? 'color: var(--danger-600); font-weight: 600;' : '' }}">
                            {{ $row['days_overdue'] }} hari
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="fi-ta-row">
                    <td class="fi-ta-cell" colspan="5" style="text-align: center; padding-block: 1.5rem">
                        <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                            Tidak ada tunggakan.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-filament-widgets::widget>
