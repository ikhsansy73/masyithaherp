<?php

namespace App\Services\Billing;

use App\Enums\PaymentMethod;
use Illuminate\Support\Carbon;

/**
 * Where a payment came from (doc 04 §6). The manual driver fills it from
 * the bendahara/operator_tu form; a future Midtrans driver calls the same
 * PaymentService::record() with method = qris and reference set — no
 * schema change needed.
 *
 * @param  array<int, int>  $allocations  invoice_id => amount
 */
final class PaymentSource
{
    /**
     * @param  array<int, int>  $allocations
     */
    public function __construct(
        public readonly int $studentId,
        public readonly Carbon $paymentDate,
        public readonly PaymentMethod $method,
        public readonly int $cashAccountId,
        public readonly int $amount,
        public readonly array $allocations = [],
        public readonly ?string $reference = null,
        public readonly ?string $notes = null,
        public readonly ?int $userId = null,
    ) {}
}
