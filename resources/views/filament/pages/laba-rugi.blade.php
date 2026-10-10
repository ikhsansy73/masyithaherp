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
        $sections = [
            ['label' => 'Pendapatan', 'groups' => $report['revenue'], 'total' => $report['revenue_total']],
            ['label' => 'Beban', 'groups' => $report['expenses'], 'total' => $report['expenses_total']],
        ];
    @endphp

    @if ($report !== null)
        <x-filament::section heading="Laba Rugi" style="margin-top: 1.5rem">
            <x-slot name="description">
                Periode {{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }}
            </x-slot>

            <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
                <colgroup>
                    <col>
                    <col style="width: 11rem">
                </colgroup>
                <tbody>
                    @foreach ($sections as $section)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" colspan="2" style="padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="font-weight: 600">{{ $section['label'] }}</div>
                            </td>
                        </tr>
                        @foreach ($section['groups'] as $group)
                            <tr class="fi-ta-row" style="border-bottom: none">
                                <td class="fi-ta-cell" colspan="2">
                                    <div class="fi-ta-cell-content" style="font-weight: 500; padding-inline-start: 1.25rem">
                                        {{ $group['header']->name }}
                                    </div>
                                </td>
                            </tr>
                            @foreach ($group['lines'] as $line)
                                <tr class="fi-ta-row" style="border-bottom: none">
                                    <td class="fi-ta-cell">
                                        <div class="fi-ta-cell-content" style="padding-inline-start: 2.5rem; color: var(--gray-600)">
                                            {{ $line['account']->name }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell" style="text-align: right">
                                        <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums; white-space: nowrap">
                                            {{ number_format($line['amount'], 0, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="fi-ta-row">
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-cell-content" style="font-weight: 500; padding-inline-start: 1.25rem">
                                        Total {{ $group['header']->name }}
                                    </div>
                                </td>
                                <td class="fi-ta-cell" style="text-align: right">
                                    <div class="fi-ta-cell-content" style="font-weight: 500; font-variant-numeric: tabular-nums; white-space: nowrap">
                                        {{ number_format($group['total'], 0, ',', '.') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" style="padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="font-weight: 600">Total {{ $section['label'] }}</div>
                            </td>
                            <td class="fi-ta-cell" style="text-align: right">
                                <div class="fi-ta-cell-content" style="font-weight: 600; font-variant-numeric: tabular-nums; white-space: nowrap">
                                    {{ number_format($section['total'], 0, ',', '.') }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="fi-ta-row" style="border-top: 2px solid var(--gray-300)">
                        <td class="fi-ta-cell" style="padding-block: 1rem">
                            <div class="fi-ta-cell-content" style="font-weight: 700; font-size: var(--text-sm)">Surplus / (Defisit)</div>
                        </td>
                        <td class="fi-ta-cell" style="text-align: right">
                            <div class="fi-ta-cell-content" style="font-weight: 700; font-size: var(--text-sm); font-variant-numeric: tabular-nums; white-space: nowrap">
                                {{ number_format($report['surplus'], 0, ',', '.') }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
