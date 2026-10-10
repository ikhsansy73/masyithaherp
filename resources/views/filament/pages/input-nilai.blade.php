<x-filament-panels::page>
    @php
        $assessment = $this->activeAssessment();
        $canSave = auth()->user()?->can('academics.assessment.create') ?? false;
    @endphp

    {{ $this->form }}

    @if ($this->selectedClassroomId() === null)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-calculator"
                heading="Pilih rombel dan semester"
                description="Pilih rombel, semester, dan penilaian di atas untuk memuat daftar siswa."
            />
        </div>
    @elseif ($this->selectedTermId() === null)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-calendar-days"
                heading="Pilih semester"
                description="Pilih semester di atas untuk memuat penilaian."
            />
        </div>
    @elseif ($this->assessmentOptionsFor((int) $this->selectedClassroomId(), (int) $this->selectedTermId()) === [])
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-clipboard-document-list"
                heading="Belum ada penilaian"
                description="Buat penilaian terlebih dahulu di menu Penilaian."
            />
        </div>
    @elseif (count($scores) === 0)
        <div style="margin-top: 1.5rem">
            <x-filament::empty-state
                icon="heroicon-o-users"
                heading="Tidak ada siswa aktif"
                description="Rombel ini belum memiliki siswa aktif."
            />
        </div>
    @else
        <x-filament::section
            heading="Daftar Nilai — {{ $assessment?->name }} (maks {{ $assessment?->max_score }})"
            style="margin-top: 1.5rem"
        >
            <x-slot name="afterHeader">
                <x-filament::badge>
                    {{ count($scores) }} siswa
                </x-filament::badge>
            </x-slot>

            <table class="fi-ta-table" style="width: 100%">
                <thead>
                    <tr>
                        <th scope="col" class="fi-ta-header-cell" style="width: 8rem">NIS</th>
                        <th scope="col" class="fi-ta-header-cell">Nama</th>
                        <th scope="col" class="fi-ta-header-cell" style="width: 10rem">Nilai</th>
                        <th scope="col" class="fi-ta-header-cell" style="width: 10rem">Predikat</th>
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
                                <x-filament::input.wrapper style="max-width: 8rem">
                                    <x-filament::input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        wire:model="scores.{{ $student->id }}.score"
                                    />
                                </x-filament::input.wrapper>
                            </td>
                            <td class="fi-ta-cell" style="padding-block: 0.75rem">
                                <x-filament::input.select
                                    wire:model="scores.{{ $student->id }}.predicate"
                                    style="min-width: 8rem"
                                >
                                    <option value="">{{ __('—') }}</option>
                                    @foreach (\App\Enums\LearningPredicate::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->getLabel() }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <x-slot name="footer">
                @if ($canSave)
                    <x-filament::button
                        wire:click="simpan"
                        icon="heroicon-o-check-badge"
                    >
                        Simpan Nilai
                    </x-filament::button>
                @endif
            </x-slot>
        </x-filament::section>
    @endif
</x-filament-panels::page>
