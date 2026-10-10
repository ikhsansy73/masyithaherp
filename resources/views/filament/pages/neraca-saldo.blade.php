<x-filament-panels::page>
    {{ $this->content }}

    @if (filled($this->reportError))
        <x-filament::section
            description="{{ $this->reportError }}"
            icon="heroicon-o-exclamation-triangle"
            iconColor="danger"
            style="margin-top: 1.5rem"
        />
    @endif

    @php
        $report = $this->report();
        $amountKeys = [
            'opening_debit', 'opening_credit',
            'mutasi_debit', 'mutasi_credit',
            'ending_debit', 'ending_credit',
        ];
    @endphp

    @if ($report !== null)

        <x-filament::section heading="Neraca Saldo" style="margin-top: 1.5rem">
            <x-slot name="description">
                Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }}
            </x-slot>

            <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
                <thead>
                    <tr>
                        <th scope="col" class="fi-ta-header-cell" rowspan="2" style="width: 7rem">Kode</th>
                        <th scope="col" class="fi-ta-header-cell" rowspan="2">Nama Akun</th>
                        <th scope="col" class="fi-ta-header-cell" colspan="2" style="text-align: center">Saldo Awal</th>
                        <th scope="col" class="fi-ta-header-cell" colspan="2" style="text-align: center">Mutasi</th>
                        <th scope="col" class="fi-ta-header-cell" colspan="2" style="text-align: center">Saldo Akhir</th>
                    </tr>
                    <tr>
                        @foreach (['Saldo Awal', 'Mutasi', 'Saldo Akhir'] as $amountGroup)
                            <th scope="col" class="fi-ta-header-cell" style="text-align: right">D</th>
                            <th scope="col" class="fi-ta-header-cell" style="text-align: right">K</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr class="fi-ta-row fi-striped">
                            <td class="fi-ta-cell" style="padding-block: 0.6rem">
                                <div class="fi-ta-cell-content" style="font-family: var(--mono-font-family); font-size: var(--text-xs); white-space: nowrap">
                                    {{ $row['code'] }}
                                </div>
                            </td>
                            <td class="fi-ta-cell">
                                <div class="fi-ta-cell-content">
                                    {{ $row['name'] }}
                                </div>
                            </td>
                            @foreach ($amountKeys as $amountKey)
                                <td class="fi-ta-cell" style="text-align: right">
                                    <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                        {{ $row[$amountKey] !== 0 ? number_format($row[$amountKey], 0, ',', '.') : '—' }}
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="8" style="text-align: center; padding-block: 2rem">
                                <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                                    Tidak ada transaksi pada periode ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr class="fi-ta-row" style="border-top: 2px solid var(--gray-300)">
                        <td class="fi-ta-cell" colspan="2" style="padding-block: 0.75rem">
                            <div class="fi-ta-cell-content" style="font-weight: 600">TOTAL</div>
                        </td>
                        @foreach ($amountKeys as $amountKey)
                            <td class="fi-ta-cell" style="text-align: right">
                                <div class="fi-ta-cell-content" style="font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap">
                                    {{ number_format($report['totals'][$amountKey], 0, ',', '.') }}
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
