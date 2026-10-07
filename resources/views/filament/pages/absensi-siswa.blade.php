<x-filament-panels::page>
    <div class="fi-section space-y-4 max-w-3xl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium mb-1" for="classroom">Rombel</label>
                <select id="classroom" wire:model.live="classroomId"
                    class="fi-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">— Pilih rombel —</option>
                    @foreach ($this->classroomOptions() as $id => $label)
                        <option value="{{ $id }}" @selected($classroomId === (int) $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="date">Tanggal</label>
                <input type="date" id="date" wire:model.live="date" value="{{ $date }}"
                    class="fi-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>
        </div>

        @if ($classroomId !== null && count($statuses) > 0)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($statuses) }} siswa</span>
                <button type="button" wire:click="semuaHadir"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium dark:bg-gray-700">
                    Tandai semua Hadir
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-2 font-medium">NIS</th>
                            <th class="px-4 py-2 font-medium">Nama</th>
                            <th class="px-4 py-2 font-medium">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->roster() as $student)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $student->nis }}</td>
                                <td class="px-4 py-2">{{ $student->full_name }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-4">
                                        @foreach (\App\Enums\StudentAttendanceStatus::cases() as $case)
                                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio"
                                                    name="statuses[{{ $student->id }}]"
                                                    value="{{ $case->value }}"
                                                    wire:model="statuses.{{ $student->id }}">
                                                <span>{{ $case->label() }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div>@error('statuses.'.$student->id) <span class="text-danger-500 text-xs">{{ $message }}</span> @enderror</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <button type="button" wire:click="simpan"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                    Simpan Absensi
                </button>
            </div>
        @elseif ($classroomId !== null)
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada siswa aktif di rombel ini.</p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih rombel untuk mengisi absensi.</p>
        @endif
    </div>
</x-filament-panels::page>
