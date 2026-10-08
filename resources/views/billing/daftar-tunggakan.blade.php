<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Tunggakan</title>
    <style>
        @page { margin: 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        .brand { font-size: 14pt; font-weight: bold; }
        .tagline { font-size: 8pt; color: #444; }
        h1 { font-size: 12pt; text-align: center; margin: 8px 0 2px; }
        .sub { text-align: center; font-size: 9pt; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5pt solid #333; padding: 3px 6px; font-size: 9pt; }
        th { background: #eee; text-align: left; }
        .num { text-align: right; }
        .sign { margin-top: 40px; width: 100%; text-align: right; font-size: 9pt; }
    </style>
</head>
<body>
    <table style="margin-bottom: 4px;">
        <tr>
            <td>
                <div class="brand">SD IT MASYITHAH</div>
                <div class="tagline">Jl. Contoh No. 1 — 0812-0000-0000</div>
            </td>
            <td style="text-align: right; font-size: 9pt;">
                Dicetak: {{ now()->translatedFormat('d F Y') }}
            </td>
        </tr>
    </table>
    <h1>DAFTAR TUNGGAKAN SPP</h1>
    <div class="sub">
        @if ($classroom) Kelas {{ $classroom->name }} · @endif
        @if ($month) Periode bulan {{ $month }} · @endif
        {{ $rows->count() }} siswa — total Rp {{ number_format($rows->sum('total'), 0, ',', '.') }}
    </div>
    <table>
        <tr>
            <th style="width: 24px;">#</th>
            <th>Siswa</th>
            <th style="width: 70px;">Kelas</th>
            <th style="width: 80px;">Tunggakan</th>
            <th style="width: 70px;">Telat</th>
            <th style="width: 60px;">Beasiswa</th>
        </tr>
        @forelse ($rows as $row)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $row['student']->full_name }}</td>
            <td>{{ $row['classroom'] ?? '-' }}</td>
            <td class="num">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
            <td>{{ $row['days_overdue'] }} hari</td>
            <td>{{ $row['has_discount'] ? 'Ya' : '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6"><em>Tidak ada tunggakan.</em></td></tr>
        @endforelse
    </table>
    <div class="sign">
        Bengkulu, {{ now()->translatedFormat('d F Y') }}<br>
        Bendahara
        <div style="margin-top: 48px;">(..............................)</div>
    </div>
</body>
</html>
