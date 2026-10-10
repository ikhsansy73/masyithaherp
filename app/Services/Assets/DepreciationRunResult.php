<?php

namespace App\Services\Assets;

use App\Models\JournalEntry;

/**
 * Outcome of one depreciation run: how many asset rows were inserted and
 * the JE #13 they were posted with (null when the run produced no rows —
 * idempotent rerun).
 */
final class DepreciationRunResult
{
    public function __construct(
        public readonly int $count,
        public readonly ?JournalEntry $entry,
    ) {}
}
