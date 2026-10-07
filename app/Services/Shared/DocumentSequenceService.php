<?php

namespace App\Services\Shared;

use App\Models\DocumentSequence;

/**
 * Shared document number generator. Must be called inside the consuming
 * transaction: the row is locked with lockForUpdate() so concurrent
 * writers serialize, and the increment commits (or rolls back) together
 * with the document that consumed the number.
 */
class DocumentSequenceService
{
    /**
     * Reserve and return the next document number, e.g. prefix "KW/2026/"
     * returns "KW/2026/000001". Zero-padded to 6 digits minimum.
     */
    public function next(string $key, string $period, string $prefix): string
    {
        $sequence = DocumentSequence::query()
            ->where('key', $key)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = DocumentSequence::query()->create([
                'key' => $key,
                'period' => $period,
                'prefix' => $prefix,
                'next_number' => 1,
            ]);
            $number = 1;
        } else {
            $number = $sequence->next_number;
        }

        $sequence->next_number = $number + 1;
        $sequence->save();

        return $sequence->prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
