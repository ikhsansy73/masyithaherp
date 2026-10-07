<x-filament-panels::page>
    {{ $this->content }}

    @php($report = $this->report())
    @if (filled($this->reportError))
        <x-filament::section
            description="{{ $this->reportError }}"
            icon="heroicon-o-exclamation-triangle"
            iconColor="danger"
        />
    @endif

    @if ($report !== null)
        <x-filament::section heading="Neraca Saldo">
            <x-slot name="description">
                Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }}
            </x-slot>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="py-2 text-start font-semibold">Kode</th>
                        <th class="py-2 text-start font-semibold">Nama Akun</th>
                        <th class="py-2 text-end font-semibold">Saldo Awal D</th>
                        <th class="py-2 text-end font-semibold">K</th>
                        <th class="py-2 text-end font-semibold">Mutasi D</th>
                        <th class="py-2 text-end font-semibold">K</th>
                        <th class="py-2 text-end font-semibold">Saldo Akhir D</th>
                        <th class="py-2 text-end font-semibold">K</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['rows'] as $row)
                        <tr class="border-b">
                            <td class="py-1.5 font-mono">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            @foreach (['opening_debit', 'opening_credit', 'mutasi_debit', 'mutasi_credit', 'ending_debit', 'ending_credit'] as $key)
                                <td class="text-end tabular-nums">
                                    {{ $row[$key] !== 0 ? number_format($row[$key], 0, ',', '.') : '—' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="font-semibold border-t-2">
                        <td class="py-2" colspan="2">TOTAL</td>
                        @foreach (['opening_debit', 'opening_credit', 'mutasi_debit', 'mutasi_credit', 'ending_debit', 'ending_credit'] as $key)
                            <td class="text-end tabular-nums">
                                {{ number_format($report['totals'][$key], 0, ',', '.') }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
