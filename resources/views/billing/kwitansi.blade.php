<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $payment->number }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        .copy { page-break-after: always; }
        .copy:last-child { page-break-after: auto; }
        .head { width: 100%; }
        .head td { vertical-align: top; }
        .brand { font-size: 15pt; font-weight: bold; }
        .tagline { font-size: 8pt; color: #444; }
        h1 { font-size: 13pt; text-align: center; margin: 10px 0 2px; letter-spacing: 3px; }
        .number { text-align: right; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        .items td, .items th { border: 0.5pt solid #333; padding: 3px 6px; font-size: 9pt; }
        .items th { background: #eee; text-align: left; }
        .terbilang { font-size: 10pt; font-style: italic; margin: 8px 0; }
        .sign { margin-top: 34px; width: 100%; }
        .sign td { text-align: center; font-size: 9pt; }
    </style>
</head>
<body>
    @foreach (['asli' => 1, 'arsip' => 2] as $copyLabel => $copyNo)
    <div class="copy">
        <table class="head">
            <tr>
                <td>
                    <div class="brand">SD IT MASYITHAH</div>
                    <div class="tagline">Jl. Contoh No. 1 — 0812-0000-0000</div>
                </td>
                <td class="number">
                    No.: <strong>{{ $payment->number }}</strong><br>
                    {{ $payment->payment_date->translatedFormat('d F Y') }}
                </td>
            </tr>
        </table>
        <h1>KWITANSI — {{ strtoupper($copyLabel) }}</h1>
        <table>
            <tr>
                <td style="width: 110px;">Terima dari</td>
                <td>: <strong>{{ $payment->student->full_name }}</strong></td>
            </tr>
            <tr>
                <td>Uang sejumlah</td>
                <td>: <em>{{ \App\Support\Terbilang::make($payment->amount) }}</em></td>
            </tr>
            <tr>
                <td>Untuk pembayaran</td>
                <td>: Pendidikan {{ $payment->student->full_name }}</td>
            </tr>
        </table>
        <table class="items">
            <tr>
                <th>Tagihan</th>
                <th style="width: 90px;">Jatuh Tempo</th>
                <th style="width: 110px;">Alokasi</th>
            </tr>
            @forelse ($payment->allocations as $allocation)
            <tr>
                <td>{{ $allocation->invoice->number }}@if ($allocation->invoice->period_month) — bulan {{ $allocation->invoice->period_month }}@endif</td>
                <td>{{ $allocation->invoice->due_date->format('d/m/Y') }}</td>
                <td style="text-align: right;">Rp {{ number_format($allocation->amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3"><em>Belum dialokasikan</em></td></tr>
            @endforelse
        </table>
        <table style="margin-top: 6px;">
            <tr>
                <td style="width: 110px;">Metode</td>
                <td>: {{ $payment->method->label() }}@if ($payment->reference) ({{ $payment->reference }})@endif</td>
            </tr>
            <tr>
                <td>Kas/Bank</td>
                <td>: {{ $payment->cashAccount->name }}</td>
            </tr>
        </table>
        <table class="sign">
            <tr>
                <td>Penerima,</td>
                <td>Pembayar,</td>
            </tr>
        </table>
        <table class="sign" style="margin-top: 46px;">
            <tr>
                <td><strong>{{ $payment->receivedBy?->name ?? 'Bendahara' }}</strong></td>
                <td><strong>{{ $payment->student->full_name }}</strong></td>
            </tr>
        </table>
    </div>
    @endforeach
</body>
</html>
