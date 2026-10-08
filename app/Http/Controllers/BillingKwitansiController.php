<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Access\Gate;

class BillingKwitansiController extends Controller
{
    public function __invoke(Payment $payment, Gate $gate)
    {
        $gate->authorize('billing.payment.viewAny');

        $payment->load([
            'student',
            'cashAccount.account',
            'allocations.invoice:id,number,period_month,due_date',
        ]);

        return Pdf::loadView('billing.kwitansi', ['payment' => $payment])
            ->setPaper('a5', 'portrait')
            ->stream("kwitansi-{$payment->number}.pdf");
    }
}
