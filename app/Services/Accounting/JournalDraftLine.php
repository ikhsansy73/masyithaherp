<?php

namespace App\Services\Accounting;

/**
 * A single debit/credit position on a JournalDraft. Exactly one of
 * debit/credit must be positive. When fundId is null the posting service
 * falls back to the account's default_fund_id.
 */
final class JournalDraftLine
{
    public function __construct(
        public readonly int $accountId,
        public readonly int $debit = 0,
        public readonly int $credit = 0,
        public readonly ?int $fundId = null,
        public readonly ?string $memo = null,
    ) {}

    public static function debit(int $accountId, int $amount, ?int $fundId = null, ?string $memo = null): self
    {
        return new self($accountId, debit: $amount, fundId: $fundId, memo: $memo);
    }

    public static function credit(int $accountId, int $amount, ?int $fundId = null, ?string $memo = null): self
    {
        return new self($accountId, credit: $amount, fundId: $fundId, memo: $memo);
    }
}
