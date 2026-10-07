<?php

namespace App\Enums;

/**
 * Invoice state machine (doc 04 §3):
 * draft → issued → partially_paid → paid; draft → cancelled;
 * issued / partially_paid → void (reversal JE, with reason).
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Void = 'void';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Terbit',
            self::PartiallyPaid => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Void => 'Dibatalkan',
            self::Cancelled => 'Dibatalkan (Draft)',
        };
    }

    /**
     * Only these statuses accept payment allocations.
     */
    public function isPayable(): bool
    {
        return in_array($this, [self::Issued, self::PartiallyPaid], true);
    }
}
