<x-filament-panels::page>
    @php
        $rows = $this->rows();
        $unallocated = $this->unallocated();
    @endphp
    <div class="fi-section space-y-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="fi-widget-subsection rounded-xl bg-danger-50 p-4 fi-color-danger dark:fi-color-danger">
                <div class="text-sm text-danger-600 dark:text-danger-400">Total Tunggakan</div>
                <div class="text-xl font-bold">Rp {{ number_format($rows->sum('total'), 0, ',', '.') }}</div>
            </div>
            <div class="fi-widget-subsection rounded-xl bg-warning-50 p-4 fi-color-warning dark:fi-color-warning">
                <div class="text-sm text-warning-600 dark:text-warning-400">Siswa Menunggak</div>
                <div class="text-xl font-bold">{{ $rows->count() }}</div>
            </div>
            <div class="fi-widget-subsection rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                <div class="text-sm text-gray-500 dark:text-gray-400">Tunggakan &gt; 90 Hari</div>
                <div class="text-xl font-bold">Rp {{ number_format($rows->sum(fn ($row) => $row['buckets'][3]), 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div>
                <h3 class="text-sm font-semibold mb-2">Per Bucket Umur</h3>
                <table class="fi-ta-table w-full text-sm">
                    <thead>
                        <tr class="text-start">
                            <th class="px-3 py-2 text-start">Bucket</th>
                            <th class="px-3 py-2 text-end">Total</th>
                            <th class="px-3 py-2 text-end">Jumlah Siswa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['1-30 hari', '31-60 hari', '61-90 hari', '> 90 hari'] as $i => $label)
                        <tr>
                            <td class="px-3 py-2">{{ $label }}</td>
                            <td class="px-3 py-2 text-end">Rp {{ number_format($rows->sum(fn ($row) => $row['buckets'][$i]), 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-end">{{ $rows->filter(fn ($row) => $row['buckets'][$i] > 0)->count() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>
                <h3 class="text-sm font-semibold mb-2">Pembayaran Belum Dialokasikan</h3>
                <table class="fi-ta-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-start">Kwitansi</th>
                            <th class="px-3 py-2 text-start">Siswa</th>
                            <th class="px-3 py-2 text-end">Belum Dialokasikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unallocated as $payment)
                        <tr>
                            <td class="px-3 py-2 font-mono text-xs">{{ $payment->number }}</td>
                            <td class="px-3 py-2">{{ $payment->student?->full_name }}</td>
                            <td class="px-3 py-2 text-end">Rp {{ number_format($payment->unallocatedAmount(), 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-3 py-2 text-gray-500">Semua pembayaran sudah dialokasikan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <h3 class="text-sm font-semibold mb-2">Daftar Siswa Menunggak</h3>
        <table class="fi-ta-table w-full text-sm">
            <thead>
                <tr>
                    <th class="px-3 py-2 text-start">#</th>
                    <th class="px-3 py-2 text-start">Siswa</th>
                    <th class="px-3 py-2 text-start">Kelas</th>
                    <th class="px-3 py-2 text-end">Total</th>
                    <th class="px-3 py-2 text-end">Telat</th>
                    <th class="px-3 py-2">Beasiswa</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                <tr>
                    <td class="px-3 py-2">{{ $loop->iteration }}</td>
                    <td class="px-3 py-2">{{ $row['student']->full_name }}</td>
                    <td class="px-3 py-2">{{ $row['classroom'] ?? '-' }}</td>
                    <td class="px-3 py-2 text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2 text-end">{{ $row['days_overdue'] }} hari</td>
                    <td class="px-3 py-2">{{ $row['has_discount'] ? 'Ya' : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-3 py-2 text-gray-500">Tidak ada tunggakan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
