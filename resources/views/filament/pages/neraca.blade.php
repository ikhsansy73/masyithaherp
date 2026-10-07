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
        <x-filament::section heading="Neraca (Posisi Keuangan)">
            <x-slot name="description">
                Per {{ $report['as_of']->format('d/m/Y') }}
            </x-slot>
            <table class="w-full text-sm">
                <tbody>
                    <tr><td colspan="2" class="py-2 font-semibold">Aset</td></tr>
                    @foreach ($report['assets'] as $row)
                        <tr class="border-b">
                            <td class="py-1 ps-4">{{ $row['account']->name }}</td>
                            <td class="text-end tabular-nums">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold border-t-2">
                        <td class="py-2">Total Aset</td>
                        <td class="text-end tabular-nums">{{ number_format($report['assets_total'], 0, ',', '.') }}</td>
                    </tr>
                    <tr><td colspan="2" class="py-2 font-semibold">Kewajiban</td></tr>
                    @foreach ($report['liabilities'] as $row)
                        <tr class="border-b">
                            <td class="py-1 ps-4">{{ $row['account']->name }}</td>
                            <td class="text-end tabular-nums">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold border-t-2">
                        <td class="py-2">Total Kewajiban</td>
                        <td class="text-end tabular-nums">{{ number_format($report['liabilities_total'], 0, ',', '.') }}</td>
                    </tr>
                    <tr><td colspan="2" class="py-2 font-semibold">Ekuitas</td></tr>
                    @foreach ($report['equity'] as $row)
                        <tr class="border-b">
                            <td class="py-1 ps-4">{{ $row['account']->name }}</td>
                            <td class="text-end tabular-nums">{{ number_format($row['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @if ($report['surplus'] !== 0)
                        <tr class="border-b">
                            <td class="py-1 ps-4 italic">Surplus / (Defisit) Berjalan</td>
                            <td class="text-end tabular-nums">{{ number_format($report['surplus'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="font-semibold border-t-2">
                        <td class="py-2">Total Ekuitas</td>
                        <td class="text-end tabular-nums">{{ number_format($report['equity_total'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="font-bold border-t-2">
                        <td class="py-3">Total Kewajiban + Ekuitas</td>
                        <td class="text-end tabular-nums">
                            {{ number_format($report['liabilities_total'] + $report['equity_total'], 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
