<?php

namespace App\Enums;

/**
 * Batch lifecycle (doc 04 §2): drafts are freely editable/deletable;
 * Terbitkan posts one batch JE; issued batches are only voidable.
 */
enum InvoiceBatchStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Terbit',
            self::Void => 'Dibatalkan',
        };
    }
}
