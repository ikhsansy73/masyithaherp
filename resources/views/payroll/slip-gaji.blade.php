<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji {{ $payslip->employee->full_name }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        .head td { vertical-align: top; }
        .brand { font-size: 15pt; font-weight: bold; }
        .tagline { font-size: 8pt; color: #444; }
        h1 { font-size: 13pt; text-align: center; margin: 10px 0 2px; letter-spacing: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .items td, .items th { padding: 2px 6px; font-size: 9pt; }
        .items th { border-bottom: 0.5pt solid #333; text-align: left; }
        .total td { border-top: 0.5pt solid #333; font-weight: bold; padding: 4px 6px; }
        .terbilang { font-size: 10pt; font-style: italic; margin: 8px 0; }
        .sign { margin-top: 34px; width: 100%; }
        .sign td { text-align: center; font-size: 9pt; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="brand">SD IT MASYITHAH</div>
                <div class="tagline">Jl. Contoh No. 1 - 0812-0000-0000</div>
            </td>
            <td style="text-align: right; font-size: 9pt;">
                Periode: <strong>{{ $payslip->payrollPeriod->monthLabel() }}</strong>
            </td>
        </tr>
    </table>
    <h1>SLIP GAJI</h1>
    <table>
        <tr>
            <td style="width: 130px;">Nama</td>
            <td>: <strong>{{ $payslip->employee->full_name }}</strong> ({{ $payslip->employee->employee_no }})</td>
        </tr>
        <tr>
            <td>Kehadiran</td>
            <td>: H {{ $payslip->days_present }} / S {{ $payslip->days_sick }} / I {{ $payslip->days_leave }} / A {{ $payslip->days_absent }}</td>
        </tr>
        @if ($payslip->notes)
        <tr>
            <td>Catatan</td>
            <td>: {{ $payslip->notes }}</td>
        </tr>
        @endif
    </table>
    <table class="items">
        <tr>
            <th>Pendapatan</th>
            <th style="width: 110px; text-align: right;">Jumlah</th>
        </tr>
        @foreach ($payslip->earnings->reject(fn ($item) => $item->salaryComponent->is_employer) as $item)
        <tr>
            <td>{{ $item->description ?? $item->salaryComponent->name }}</td>
            <td style="text-align: right;">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td>Total Pendapatan</td>
            <td style="text-align: right;">Rp {{ number_format($payslip->total_earnings, 0, ',', '.') }}</td>
        </tr>
    </table>
    <table class="items" style="margin-top: 10px;">
        <tr>
            <th>Potongan</th>
            <th style="width: 110px; text-align: right;">Jumlah</th>
        </tr>
        @forelse ($payslip->deductions as $item)
        <tr>
            <td>{{ $item->description ?? $item->salaryComponent->name }}</td>
            <td style="text-align: right;">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
        </tr>
        @empty
        <tr><td colspan="2"><em>-</em></td></tr>
        @endforelse
        <tr class="total">
            <td>Total Potongan</td>
            <td style="text-align: right;">Rp {{ number_format($payslip->total_deductions, 0, ',', '.') }}</td>
        </tr>
    </table>
    <p style="font-size: 8pt; color: #444; margin: 4px 0;">BPJS ditanggung sekolah: Rp {{ number_format($payslip->items->filter(fn ($item) => $item->salaryComponent->is_employer)->sum('amount'), 0, ',', '.') }}</p>
    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 130px;">Diterima Bersih</td>
            <td>: <strong>Rp {{ number_format($payslip->net_salary, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td>Perkiraan</td>
            <td>: <em>{{ \App\Support\Terbilang::make($payslip->net_salary) }}</em></td>
        </tr>
    </table>
    <table class="sign">
        <tr>
            <td>Penerima,</td>
            <td>Bendahara,</td>
        </tr>
    </table>
    <table class="sign" style="margin-top: 46px;">
        <tr>
            <td><strong>{{ $payslip->employee->full_name }}</strong></td>
            <td><strong>Bendahara</strong></td>
        </tr>
    </table>
</body>
</html>
