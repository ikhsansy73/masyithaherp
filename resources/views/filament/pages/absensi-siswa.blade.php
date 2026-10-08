<x-filament-panels::page>
    @php
        $counts = $this->statusCounts();
    @endphp

    {{ $this->form }}

    @if ($this->selectedClassroomId() === null)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-clipboard-document-check"
                heading="Pilih rombel terlebih dahulu"
                description="Pilih rombel dan tanggal di atas untuk memuat daftar siswa."
            />
        </div>
    @elseif (count($statuses) === 0)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-users"
                heading="Tidak ada siswa aktif"
                description="Rombel ini belum memiliki siswa aktif."
            />
        </div>
    @else
        <x-filament::section
            heading="Daftar Siswa — {{ count($statuses) }} orang"
            style="margin-top: 1.5rem"
        >
            <x-slot name="afterHeader">
                @foreach (App\Enums\StudentAttendanceStatus::cases() as $case)
                    <x-filament::badge :color="$case->getColor()">
                        {{ $case->getLabel() }}: {{ $counts[$case->value] ?? 0 }}
                    </x-filament::badge>
                @endforeach

                <x-filament::button
                    size="sm"
                    color="gray"
                    wire:click="semuaHadir"
                >
                    Tandai semua Hadir
                </x-filament::button>
            </x-slot>

            <table class="fi-ta-table" style="width: 100%">
                <thead>
                    <tr>
                        <th scope="col" class="fi-ta-header-cell" style="width: 8rem">NIS</th>
                        <th scope="col" class="fi-ta-header-cell">Nama</th>
                        <th scope="col" class="fi-ta-header-cell" style="width: 45%">Kehadiran</th>
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
                                    @foreach (App\Enums\StudentAttendanceStatus::cases() as $case)
                                        <x-filament::button
                                            size="xs"
                                            :color="$statuses[$student->id] === $case->value ? $case->getColor() : 'gray'"
                                            :outlined="$statuses[$student->id] !== $case->value"
                                            wire:click="setStatus({{ $student->id }}, '{{ $case->value }}')"
                                        >
                                            {{ $case->getLabel() }}
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
                    icon="heroicon-o-check-badge"
                >
                    Simpan Absensi
                </x-filament::button>
            </x-slot>
        </x-filament::section>
    @endif
</x-filament-panels::page>
