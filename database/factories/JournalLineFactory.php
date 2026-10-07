<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalLine>
 */
class JournalLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'fund_id' => null,
            'debit' => 0,
            'credit' => 0,
            'memo' => null,
        ];
    }

    public function debit(int $amount): static
    {
        return $this->state(fn (): array => ['debit' => $amount, 'credit' => 0]);
    }

    public function credit(int $amount): static
    {
        return $this->state(fn (): array => ['debit' => 0, 'credit' => $amount]);
    }
}
