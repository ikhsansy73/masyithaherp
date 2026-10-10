<x-filament-panels::page>
    {{ $this->content }}

    @php
        $rows = $this->rows();
        $unallocated = $this->unallocated();
        $overdue90 = $rows->sum(fn ($row) => $row['buckets'][3]);
    @endphp

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; margin-top: 1.5rem">
        <x-filament::section heading="Total Tunggakan">
            <div style="font-size: var(--text-xl); font-weight: 700; color: var(--danger-600); font-variant-numeric: tabular-nums">
                Rp {{ number_format($rows->sum('total'), 0, ',', '.') }}
            </div>
        </x-filament::section>

        <x-filament::section heading="Siswa Menunggak">
            <div style="font-size: var(--text-xl); font-weight: 700; color: var(--warning-600); font-variant-numeric: tabular-nums">
                {{ $rows->count() }}
            </div>
        </x-filament::section>

        <x-filament::section heading="Tunggakan > 90 Hari">
            <div style="font-size: var(--text-xl); font-weight: 700; font-variant-numeric: tabular-nums">
                Rp {{ number_format($overdue90, 0, ',', '.') }}
            </div>
        </x-filament::section>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: 1rem; margin-top: 1.5rem">
        <x-filament::section heading="Per Bucket Umur">
            <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
                <thead>
                    <tr>
                        <th scope="col" class="fi-ta-header-cell">Bucket</th>
                        <th scope="col" class="fi-ta-header-cell" style="text-align: right">Total</th>
                        <th scope="col" class="fi-ta-header-cell" style="text-align: right">Jumlah Siswa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['1-30 hari', '31-60 hari', '61-90 hari', '> 90 hari'] as $bucketLabel)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" style="padding-block: 0.6rem">
                                <div class="fi-ta-cell-content">
                                    {{ $bucketLabel }}
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="text-align: right">
                                <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                    {{ $rows->sum(fn ($row) => $row['buckets'][$loop->index]) !== 0
                                        ? 'Rp '.number_format($rows->sum(fn ($row) => $row['buckets'][$loop->index]), 0, ',', '.')
                                        : '—' }}
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="text-align: right">
                                <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums">
                                    {{ $rows->filter(fn ($row) => $row['buckets'][$loop->index] > 0)->count() }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Pembayaran Belum Dialokasikan">
            <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
                <thead>
                    <tr>
                        <th scope="col" class="fi-ta-header-cell">Kwitansi</th>
                        <th scope="col" class="fi-ta-header-cell">Siswa</th>
                        <th scope="col" class="fi-ta-header-cell" style="text-align: right">Belum Dialokasikan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($unallocated as $payment)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" style="padding-block: 0.6rem">
                                <div class="fi-ta-cell-content" style="font-family: var(--mono-font-family); font-size: var(--text-xs); white-space: nowrap">
                                    {{ $payment->number }}
                                </div>
                            </td>
                            <td class="fi-ta-cell">
                                <div class="fi-ta-cell-content">
                                    {{ $payment->student?->full_name }}
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="text-align: right">
                                <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                    Rp {{ number_format($payment->unallocatedAmount(), 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="3" style="text-align: center; padding-block: 1.5rem">
                                <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                                    Semua pembayaran sudah dialokasikan.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>

    <x-filament::section heading="Daftar Siswa Menunggak" style="margin-top: 1.5rem">
        <x-slot name="afterHeader">
            <x-filament::badge :color="$rows->isEmpty() ? 'success' : 'danger'">
                {{ $rows->count() }} siswa
            </x-filament::badge>
        </x-slot>

        <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
            <thead>
                <tr>
                    <th scope="col" class="fi-ta-header-cell" style="width: 3rem">#</th>
                    <th scope="col" class="fi-ta-header-cell">Siswa</th>
                    <th scope="col" class="fi-ta-header-cell" style="width: 7rem">Kelas</th>
                    <th scope="col" class="fi-ta-header-cell" style="text-align: right">Total</th>
                    <th scope="col" class="fi-ta-header-cell" style="text-align: right">Telat</th>
                    <th scope="col" class="fi-ta-header-cell" style="width: 7rem">Beasiswa</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
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
                        <td class="fi-ta-cell">
                            <div class="fi-ta-cell-content">
                                {{ $row['has_discount'] ? 'Ya' : '-' }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="fi-ta-row">
                        <td class="fi-ta-cell" colspan="6" style="text-align: center; padding-block: 2rem">
                            <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                                Tidak ada tunggakan.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>
