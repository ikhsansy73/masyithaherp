<x-filament-widgets::widget>
    <h3 class="text-sm font-semibold mb-2 px-2">Tunggakan Teratas</h3>
    <table class="fi-ta-table w-full text-sm">
        <thead>
            <tr>
                <th class="px-3 py-2 text-start">#</th>
                <th class="px-3 py-2 text-start">Siswa</th>
                <th class="px-3 py-2 text-start">Kelas</th>
                <th class="px-3 py-2 text-end">Tunggakan</th>
                <th class="px-3 py-2 text-end">Telat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->topArrears() as $row)
            <tr>
                <td class="px-3 py-2">{{ $loop->iteration }}</td>
                <td class="px-3 py-2">{{ $row['student']->full_name }}</td>
                <td class="px-3 py-2">{{ $row['classroom'] ?? '-' }}</td>
                <td class="px-3 py-2 text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                <td class="px-3 py-2 text-end {{ $row['days_overdue'] > 60 ? 'text-danger-600' : '' }}">{{ $row['days_overdue'] }} hari</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-3 py-2 text-gray-500">Tidak ada tunggakan.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-filament-widgets::widget>
