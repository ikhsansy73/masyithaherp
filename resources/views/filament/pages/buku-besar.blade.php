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
        <x-filament::section heading="Buku Besar — {{ $report['account']['name'] }}">
            <x-slot name="description">
                {{ $report['account']['code'] }} · Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }} ·
                Saldo awal: Rp {{ number_format($report['opening'], 0, ',', '.') }}
            </x-slot>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="py-2 text-start font-semibold">Tanggal</th>
                        <th class="py-2 text-start font-semibold">No. Jurnal</th>
                        <th class="py-2 text-start font-semibold">Keterangan</th>
                        <th class="py-2 text-end font-semibold">Debit</th>
                        <th class="py-2 text-end font-semibold">Kredit</th>
                        <th class="py-2 text-end font-semibold">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b italic">
                        <td colspan="3" class="py-1.5">Saldo awal</td>
                        <td colspan="2"></td>
                        <td class="text-end tabular-nums">{{ number_format($report['opening'], 0, ',', '.') }}</td>
                    </tr>
                    @foreach ($report['lines'] as $line)
                        <tr class="border-b">
                            <td class="py-1.5">{{ $line['entry_date']->format('d/m/Y') }}</td>
                            <td class="font-mono">{{ $line['number'] }}</td>
                            <td>{{ $line['description'] }}</td>
                            <td class="text-end tabular-nums">
                                {{ $line['debit'] !== 0 ? number_format($line['debit'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-end tabular-nums">
                                {{ $line['credit'] !== 0 ? number_format($line['credit'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-end tabular-nums font-semibold">
                                {{ number_format($line['running'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold border-t-2">
                        <td colspan="3" class="py-2">Saldo akhir</td>
                        <td colspan="2"></td>
                        <td class="text-end tabular-nums">{{ number_format($report['closing'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
