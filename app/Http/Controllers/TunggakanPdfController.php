<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Services\Billing\ArrearsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;

class TunggakanPdfController extends Controller
{
    public function __invoke(Request $request, ArrearsService $arrears, Gate $gate)
    {
        $gate->authorize('billing.arrears.view');

        $rows = $arrears->arrears();

        if (filled($request->query('kelas'))) {
            $rows = $rows->filter(fn (array $row): bool => $row['classroom'] === $request->query('kelas'));
        }

        if (filled($request->query('beasiswa'))) {
            $onlyDiscounted = $request->boolean('beasiswa');
            $rows = $rows->filter(fn (array $row): bool => $row['has_discount'] === $onlyDiscounted);
        }

        if (filled($request->query('bulan'))) {
            $month = (int) $request->query('bulan');
            $invoiceIds = $rows->flatMap(fn (array $row): array => $row['invoice_ids'])->all();
            $months = \App\Models\Invoice::query()->whereKey($invoiceIds)->pluck('period_month', 'id');
            $rows = $rows->filter(fn (array $row): bool => collect($row['invoice_ids'])
                ->contains(fn (int $invoiceId): bool => (int) $months[$invoiceId] === $month));
        }

        $classroom = filled($request->query('kelas'))
            ? Classroom::query()->where('name', $request->query('kelas'))->first()
            : null;

        return Pdf::loadView('billing.daftar-tunggakan', [
            'rows' => $rows->values(),
            'classroom' => $classroom,
            'month' => $request->query('bulan'),
        ])
            ->setPaper('a4', 'portrait')
            ->stream('daftar-tunggakan.pdf');
    }
}
