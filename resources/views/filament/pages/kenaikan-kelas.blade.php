<x-filament-panels::page>
    <div class="space-y-4 max-w-4xl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium mb-1" for="classroom">Rombel Sumber</label>
                <select id="classroom" wire:model.live="classroomId"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">— Pilih rombel —</option>
                    @foreach ($this->classroomOptions() as $id => $label)
                        <option value="{{ $id }}" @selected($classroomId === (int) $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="targetYear">Tahun Ajaran Target</label>
                <select id="targetYear" wire:model.live="targetYearId"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">— Pilih tahun —</option>
                    @foreach ($this->targetYearOptions() as $id => $label)
                        <option value="{{ $id }}" @selected($targetYearId === (int) $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="movementDate">Tanggal Mutasi</label>
                <input type="date" id="movementDate" wire:model="movementDate" value="{{ $movementDate }}"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>
        </div>

        @if ($classroomId !== null && $targetYearId !== null && count($decisions) > 0)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($decisions) }} siswa aktif</span>
                <button type="button" wire:click="semuaNaik"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium dark:bg-gray-700">
                    Semua Naik Kelas
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left dark:bg-gray-800/60">
                        <tr>
                            <th class="px-4 py-2 font-medium">NIS</th>
                            <th class="px-4 py-2 font-medium">Nama</th>
                            <th class="px-4 py-2 font-medium">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->roster() as $student)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $student->nis }}</td>
                                <td class="px-4 py-2">{{ $student->full_name }}</td>
                                <td class="px-4 py-2">
                                    <select wire:model="decisions.{{ $student->id }}"
                                        class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-800">
                                        @foreach (\App\Services\School\StudentMovementService::DECISIONS as $decision)
                                            <option value="{{ $decision }}">{{ ucfirst($decision) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Catatan</label>
                <textarea wire:model="notes" rows="2"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"></textarea>
            </div>

            <div>
                <button type="button" wire:click="simpan" wire:confirm="Proses kenaikan kelas untuk seluruh siswa yang dipilih?"
                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                    Proses Kenaikan Kelas
                </button>
            </div>
        @elseif ($classroomId !== null && $targetYearId !== null)
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada siswa aktif di rombel ini.</p>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih rombel sumber dan tahun ajaran target untuk memulai.</p>
        @endif
    </div>
</x-filament-panels::page>
