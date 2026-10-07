<?php

namespace App\Services\Accounting;

use App\Enums\JournalSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An in-memory journal entry to be posted through
 * JournalPostingService::post() — the only write path for journals
 * (doc 03 §3.1).
 */
final class JournalDraft
{
    /**
     * @param  list<JournalDraftLine>  $lines
     */
    public function __construct(
        public readonly Carbon $entryDate,
        public readonly string $description,
        public readonly array $lines,
        public readonly int $userId,
        public readonly JournalSource $source = JournalSource::Manual,
        public readonly ?Model $reference = null,
    ) {}
}
