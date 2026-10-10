<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Access\Gate;

class PayrollSlipController extends Controller
{
    public function __invoke(Payslip $payslip, Gate $gate)
    {
        $gate->authorize('payroll.view');

        // A slip is an official document only once the period is locked
        // (approved/paid) — drafts have no downloadable slip.
        if (! $payslip->payrollPeriod->status->isLocked()) {
            abort(403, 'Slip gaji tersedia setelah payroll disetujui.');
        }

        $payslip->load([
            'employee',
            'items.salaryComponent',
        ]);

        return Pdf::loadView('payroll.slip-gaji', ['payslip' => $payslip])
            ->setPaper('a5', 'portrait')
            ->stream("slip-gaji-{$payslip->employee->employee_no}-{$payslip->employee->full_name}.pdf");
    }
}
