<x-filament-panels::page>
    @php
        $naikCount = $this->naikCount();
    @endphp

    {{ $this->form }}

    @if ($this->selectedClassroomId() === null || $this->selectedTargetYearId() === null)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-arrow-up-right"
                heading="Pilih rombel sumber dan tahun ajaran target"
                description="Pilih rombel sumber dan tahun ajaran target di atas untuk memuat daftar siswa."
            />
        </div>
    @elseif (count($decisions) === 0)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-users"
                heading="Tidak ada siswa aktif"
                description="Rombel ini belum memiliki siswa aktif."
            />
        </div>
    @else
        <x-filament::section
            heading="Daftar Siswa — {{ count($decisions) }} orang"
            style="margin-top: 1.5rem"
        >
            <x-slot name="afterHeader">
                <x-filament::badge :color="$naikCount === count($decisions) ? 'success' : 'warning'">
                    Naik: {{ $naikCount }} / {{ count($decisions) }}
                </x-filament::badge>

                <x-filament::button
                    size="sm"
                    color="gray"
                    wire:click="semuaNaik"
                >
                    Semua Naik Kelas
                </x-filament::button>
            </x-slot>

            <table class="fi-ta-table" style="width: 100%">
                <thead>
                    <tr>
                        <th scope="col" class="fi-ta-header-cell" style="width: 8rem">NIS</th>
                        <th scope="col" class="fi-ta-header-cell">Nama</th>
                        <th scope="col" class="fi-ta-header-cell" style="width: 55%">Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->roster() as $student)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell" style="padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="font-family: var(--mono-font-family); font-size: var(--text-xs)">
                                    {{ $student->nis }}
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="padding-block: 0.75rem">
                                <div class="fi-ta-cell-content" style="font-weight: 500">
                                    {{ $student->full_name }}
                                </div>
                            </td>
                            <td class="fi-ta-cell" style="padding-block: 0.75rem">
                                <div class="fi-btn-group">
                                    @foreach (App\Services\School\StudentMovementService::DECISIONS as $decision)
                                        <x-filament::button
                                            size="xs"
                                            :color="$decisions[$student->id] === $decision ? $this->decisionColor($decision) : 'gray'"
                                            :outlined="$decisions[$student->id] !== $decision"
                                            wire:click="setDecision({{ $student->id }}, '{{ $decision }}')"
                                        >
                                            {{ $this->decisionLabel($decision) }}
                                        </x-filament::button>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-slot name="footer">
                <x-filament::button
                    wire:click="simpan"
                    wire:confirm="Proses kenaikan kelas untuk seluruh siswa yang dipilih?"
                    icon="heroicon-o-arrow-up-right"
                >
                    Proses Kenaikan Kelas
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    @endif
</x-filament-panels::page>
