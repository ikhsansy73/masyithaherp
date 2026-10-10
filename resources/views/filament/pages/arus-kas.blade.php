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
    @endphp

    @if ($report !== null)
        <x-filament::section heading="Arus Kas" style="margin-top: 1.5rem">
            <x-slot name="description">
                Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }} ·
                Saldo kas awal: Rp {{ number_format($report['opening'], 0, ',', '.') }}
            </x-slot>

            <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
                <colgroup>
                    <col>
                    <col style="width: 11rem">
                </colgroup>
                <tbody>
                    @foreach (['operasi' => 'Arus Kas Operasi', 'investasi' => 'Arus Kas Investasi', 'pendanaan' => 'Arus Kas Pendanaan'] as $bucket => $label)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="2" style="padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="font-weight: 600">{{ $label }}</div>
                            </td>
                        </tr>
                        @if ($report[$bucket]['in'] !== 0)
                            <tr class="fi-ta-row" style="border-bottom: none">
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-cell-content" style="padding-inline-start: 1.25rem; color: var(--gray-600)">
                                        Kas masuk
                                    </div>
                                </td>
                                <td class="fi-ta-cell" style="text-align: right">
                                    <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                        {{ number_format($report[$bucket]['in'], 0, ',', '.') }}
                                    </div>
                                </td>
                            </tr>
                        @endif
                        @if ($report[$bucket]['out'] !== 0)
                            <tr class="fi-ta-row" style="border-bottom: none">
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-cell-content" style="padding-inline-start: 1.25rem; color: var(--gray-600)">
                                        Kas keluar
                                    </div>
                                </td>
                                <td class="fi-ta-cell" style="text-align: right">
                                    <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                        ({{ number_format($report[$bucket]['out'], 0, ',', '.') }})
                                    </div>
                                </td>
                            </tr>
                        @endif
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell">
                                <div class="fi-ta-cell-content" style="font-weight: 500; padding-inline-start: 1.25rem">
                                    Kas bersih {{ strtolower(mb_substr($label, 9)) }}
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="text-align: right">
                                <div class="fi-ta-cell-content" style="font-weight: 500; font-variant-numeric: tabular-nums; white-space: nowrap">
                                    {{ number_format($report[$bucket]['net'], 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if ($report['non_kas']['net'] !== 0)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" style="padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="padding-inline-start: 1.25rem; color: var(--gray-600)">
                                    Item non kas
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="text-align: right; padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                    {{ number_format($report['non_kas']['net'], 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    @endif
                    <tr class="fi-ta-row" style="border-top: 2px solid var(--gray-300)">
                        <td class="fi-ta-cell" style="padding-block: 1rem">
                            <div class="fi-ta-cell-content" style="font-weight: 700; font-size: var(--text-sm)">
                                Kenaikan / (Penurunan) Bersih Kas
                            </div>
                        </td>
                        <td class="fi-ta-cell" style="text-align: right">
                            <div class="fi-ta-cell-content" style="font-weight: 700; font-size: var(--text-sm); font-variant-numeric: tabular-nums; white-space: nowrap">
                                {{ number_format($report['closing'] - $report['opening'], 0, ',', '.') }}
                            </div>
                        </td>
                    </tr>
                    <tr class="fi-ta-row">
                        <td class="fi-ta-cell" style="padding-block: 0.75rem">
                            <div class="fi-ta-cell-content" style="font-weight: 600">
                                Saldo kas akhir
                            </div>
                        </td>
                        <td class="fi-ta-cell" style="text-align: right">
                            <div class="fi-ta-cell-content" style="font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap">
                                {{ number_format($report['closing'], 0, ',', '.') }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
