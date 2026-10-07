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
        <x-filament::section heading="Arus Kas">
            <x-slot name="description">
                Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }} ·
                Saldo kas awal: Rp {{ number_format($report['opening'], 0, ',', '.') }}
            </x-slot>
            <table class="w-full text-sm">
                <tbody>
                    @foreach (['operasi' => 'Arus Kas Operasi', 'investasi' => 'Arus Kas Investasi', 'pendanaan' => 'Arus Kas Pendanaan'] as $bucket => $label)
                        <tr>
                            <td colspan="2" class="py-2 font-semibold">{{ $label }}</td>
                        </tr>
                        @if ($report[$bucket]['in'] !== 0)
                            <tr class="border-b">
                                <td class="py-1 ps-4">Kas masuk</td>
                                <td class="text-end tabular-nums">{{ number_format($report[$bucket]['in'], 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if ($report[$bucket]['out'] !== 0)
                            <tr class="border-b">
                                <td class="py-1 ps-4">Kas keluar</td>
                                <td class="text-end tabular-nums">({{ number_format($report[$bucket]['out'], 0, ',', '.') }})</td>
                            </tr>
                        @endif
                        <tr class="font-semibold">
                            <td class="py-1 ps-4">Kas bersih {{ strtolower(mb_substr($label, 9)) }}</td>
                            <td class="text-end tabular-nums">{{ number_format($report[$bucket]['net'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @if ($report['non_kas']['net'] !== 0)
                        <tr class="border-b">
                            <td class="py-1 pt-3">Item non kas</td>
                            <td class="text-end tabular-nums pt-3">{{ number_format($report['non_kas']['net'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="font-bold border-t-2 text-lg">
                        <td class="py-3">Kenaikan / (Penurunan) Bersih Kas</td>
                        <td class="text-end tabular-nums">{{ number_format($report['closing'] - $report['opening'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="font-semibold">
                        <td class="py-1">Saldo kas akhir</td>
                        <td class="text-end tabular-nums">{{ number_format($report['closing'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
