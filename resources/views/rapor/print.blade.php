<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Hasil Belajar</title>
<style>
    @page { margin: 36px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    .kop { width: 100%; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 4px; }
    .kop td { vertical-align: middle; }
    .kop .school { font-size: 16px; font-weight: bold; text-transform: uppercase; }
    .kop .sub { font-size: 10px; }
    .title { text-align: center; font-weight: bold; font-size: 13px; margin: 10px 0 12px; text-decoration: underline; }
    .identity { width: 100%; margin-bottom: 12px; }
    .identity td { padding: 1px 4px; font-size: 11px; }
    .label { width: 130px; }
    table.nilai { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.nilai th, table.nilai td { border: 1px solid #111; padding: 4px 6px; vertical-align: top; }
    table.nilai th { background-color: #eee; text-align: center; font-size: 11px; }
    .section-title { font-weight: bold; margin: 10px 0 4px; }
    table.plain { width: 100%; border-collapse: collapse; }
    table.plain td, table.plain th { border: 1px solid #111; padding: 4px 6px; font-size: 11px; }
    .signature { width: 100%; margin-top: 28px; }
    .signature td { width: 33%; text-align: center; vertical-align: top; font-size: 11px; }
    .sign-name { margin-top: 62px; font-weight: bold; text-decoration: underline; }
    .sign-nip { font-size: 10px; }
    .page-break { page-break-after: always; }
</style>
</head>
<body>
@foreach ($cards as $card)
    <table class="kop">
        <tr>
            <td style="width: 64px">
                @if ($logoData)
                    <img src="{{ $logoData }}" style="width: 60px">
                @endif
            </td>
            <td>
                <div class="school">{{ $school->school_name }}</div>
                <div class="sub">{{ $school->address }}</div>
                <div class="sub">Telp. {{ $school->phone }} · {{ $school->email }} · NPSN: {{ $school->npsn }}</div>
            </td>
        </tr>
    </table>

    <div class="title">LAPORAN HASIL BELAJAR</div>

    <table class="identity">
        <tr>
            <td class="label">Nama Peserta Didik</td>
            <td>: {{ $card->student->full_name }}</td>
            <td class="label">Kelas</td>
            <td>: {{ $card->classroom->name }} (Fase {{ $card->classroom->fase }})</td>
        </tr>
        <tr>
            <td class="label">NIS</td>
            <td>: {{ $card->student->nis }}</td>
            <td class="label">Semester</td>
            <td>: {{ $card->term->name }} — {{ $card->term->academicYear?->name }}</td>
        </tr>
    </table>

    <table class="nilai">
        <tr>
            <th style="width: 5%">No</th>
            <th style="width: 22%">Mata Pelajaran</th>
            <th style="width: 9%">Nilai</th>
            <th style="width: 9%">Predikat</th>
            <th>Deskripsi Capaian</th>
        </tr>
        @foreach ($card->subjects->sortBy('subject.name') as $index => $row)
            <tr>
                <td style="text-align: center">{{ $index + 1 }}</td>
                <td>{{ $row->subject?->name }}</td>
                <td style="text-align: center">{{ number_format((float) $row->final_score, 0) }}</td>
                <td style="text-align: center">{{ $row->predicate instanceof \BackedEnum ? $row->predicate->value : $row->predicate }}</td>
                <td>{{ $row->description }}</td>
            </tr>
        @endforeach
    </table>

    @php
        $ekskul = $card->extracurriculars;
        $prestasi = $card->achievements;
    @endphp

    <div class="section-title">Ekstrakurikuler</div>
    <table class="plain">
        <tr>
            <th style="width: 5%">No</th>
            <th style="width: 30%">Kegiatan</th>
            <th style="width: 12%">Predikat</th>
            <th>Keterangan</th>
        </tr>
        @forelse ($ekskul as $index => $row)
            <tr>
                <td style="text-align: center">{{ $index + 1 }}</td>
                <td>{{ $row->extracurricular?->name }}</td>
                <td style="text-align: center">{{ $row->predicate instanceof \BackedEnum ? $row->predicate->value : $row->predicate }}</td>
                <td>{{ $row->description ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align: center">-</td>
            </tr>
        @endforelse
    </table>

    <div class="section-title">Prestasi</div>
    <table class="plain">
        <tr>
            <th style="width: 5%">No</th>
            <th style="width: 40%">Nama Prestasi</th>
            <th style="width: 15%">Jenis</th>
            <th style="width: 15%">Tingkat</th>
            <th>Juara</th>
        </tr>
        @forelse ($prestasi as $index => $row)
            <tr>
                <td style="text-align: center">{{ $index + 1 }}</td>
                <td>{{ $row->name }}</td>
                <td style="text-align: center">{{ $row->type instanceof \BackedEnum ? $row->type->getLabel() : $row->type }}</td>
                <td style="text-align: center">{{ $row->level instanceof \BackedEnum ? $row->level->getLabel() : $row->level }}</td>
                <td style="text-align: center">{{ $row->rank ? 'Juara '.$row->rank : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center">-</td>
            </tr>
        @endforelse
    </table>

    <div class="section-title">Kehadiran</div>
    <table class="plain" style="width: 60%">
        <tr>
            <td>Sakit</td>
            <td style="text-align: center">: {{ $card->days_sick }} hari</td>
            <td>Izin</td>
            <td style="text-align: center">: {{ $card->days_izin }} hari</td>
            <td>Tanpa Keterangan</td>
            <td style="text-align: center">: {{ $card->days_alpa }} hari</td>
        </tr>
    </table>

    <div class="section-title">Catatan Wali Kelas</div>
    <div style="min-height: 30px; border: 1px solid #111; padding: 6px">
        {{ $card->catatan_wali_kelas ?? '-' }}
    </div>

    <table class="signature">
        <tr>
            <td>
                Orang Tua/Wali,
                <div class="sign-name">&nbsp;</div>
            </td>
            <td>
                Wali Kelas,
                <div class="sign-name">{{ $waliKelas?->full_name ?? '-' }}</div>
                @if ($waliKelas?->nip)
                    <div class="sign-nip">NIP. {{ $waliKelas->nip }}</div>
                @endif
            </td>
            <td>
                {{ now()->translatedFormat('d F Y') }}<br>
                Kepala Sekolah,
                <div class="sign-name">{{ $school->headmaster_name }}</div>
                <div class="sign-nip">NIP. {{ $school->headmaster_nip }}</div>
            </td>
        </tr>
    </table>

    @if (! $loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
