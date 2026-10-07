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
        <x-filament::section heading="Laba Rugi">
            <x-slot name="description">
                Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }}
            </x-slot>
            <table class="w-full text-sm">
                <tbody>
                    <tr><td colspan="2" class="py-2 font-semibold">Pendapatan</td></tr>
                    @foreach ($report['revenue'] as $group)
                        <tr>
                            <td class="py-1 ps-4 italic" colspan="2">{{ $group['header']->name }}</td>
                        </tr>
                        @foreach ($group['lines'] as $line)
                            <tr class="border-b">
                                <td class="py-1 ps-8">{{ $line['account']->name }}</td>
                                <td class="text-end tabular-nums">{{ number_format($line['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="py-1 ps-4 font-semibold">Total {{ $group['header']->name }}</td>
                            <td class="text-end tabular-nums font-semibold">{{ number_format($group['total'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold border-t-2">
                        <td class="py-2">Total Pendapatan</td>
                        <td class="text-end tabular-nums">{{ number_format($report['revenue_total'], 0, ',', '.') }}</td>
                    </tr>
                    <tr><td colspan="2" class="py-2 font-semibold">Beban</td></tr>
                    @foreach ($report['expenses'] as $group)
                        <tr>
                            <td class="py-1 ps-4 italic" colspan="2">{{ $group['header']->name }}</td>
                        </tr>
                        @foreach ($group['lines'] as $line)
                            <tr class="border-b">
                                <td class="py-1 ps-8">{{ $line['account']->name }}</td>
                                <td class="text-end tabular-nums">{{ number_format($line['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="py-1 ps-4 font-semibold">Total {{ $group['header']->name }}</td>
                            <td class="text-end tabular-nums font-semibold">{{ number_format($group['total'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold border-t-2">
                        <td class="py-2">Total Beban</td>
                        <td class="text-end tabular-nums">{{ number_format($report['expenses_total'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="font-bold border-t-2 text-lg">
                        <td class="py-3">Surplus / (Defisit)</td>
                        <td class="text-end tabular-nums">{{ number_format($report['surplus'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
