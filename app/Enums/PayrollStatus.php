<?php

namespace App\Enums;

/**
 * Payroll state machine (doc 05 §3):
 * draft → calculated → approved → paid; cancelled only from draft/calculated.
 */
enum PayrollStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Calculated => 'Dihitung',
            self::Approved => 'Disetujui',
            self::Paid => 'Dibayar',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Calculated => 'info',
            self::Approved => 'warning',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Slips and components-in-effect are frozen once approved.
     */
    public function isLocked(): bool
    {
        return in_array($this, [self::Approved, self::Paid], true);
    }

    /**
     * Only before approval can the run still be recalculated or cancelled.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Calculated], true);
    }
}
