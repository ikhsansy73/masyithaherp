<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Doc 03 §3.2: daily reconciliation — every journal entry must balance
 * (SUM(debit) = SUM(credit)); any deviation is reported to super_admin.
 */
class VerifyBalance extends Command
{
    protected $signature = 'accounting:verify-balance';

    protected $description = 'Memeriksa seluruh jurnal agar total debit sama dengan kredit, dan memberi tahu super_admin bila ada penyimpangan.';

    public function handle(): int
    {
        $unbalanced = $this->unbalancedEntries();

        if ($unbalanced->isEmpty()) {
            $this->info('Seluruh jurnal seimbang.');

            return self::SUCCESS;
        }

        foreach ($unbalanced as $entry) {
            $this->error(sprintf(
                '%s — %s: debit %s, kredit %s.',
                $entry->number,
                $entry->description,
                number_format((int) $entry->debit_total, 0, ',', '.'),
                number_format((int) $entry->credit_total, 0, ',', '.'),
            ));
        }

        $this->notifySuperAdmins($unbalanced);

        $this->error($unbalanced->count().' jurnal tidak seimbang. Super_admin telah diberi tahu.');

        return self::FAILURE;
    }

    /**
     * All entries — voided ones included: a void pair nets to zero, so
     * only genuinely broken entries surface here.
     *
     * @return Collection<int, object>
     */
    private function unbalancedEntries(): Collection
    {
        return JournalEntry::query()
            ->select('journal_entries.id', 'journal_entries.number', 'journal_entries.description')
            ->selectRaw('SUM(journal_lines.debit) as debit_total, SUM(journal_lines.credit) as credit_total')
            ->join('journal_lines', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->groupBy('journal_entries.id', 'journal_entries.number', 'journal_entries.description')
            ->havingRaw('SUM(journal_lines.debit) <> SUM(journal_lines.credit)')
            ->get();
    }

    /**
     * @param  Collection<int, object>  $entries
     */
    private function notifySuperAdmins(Collection $entries): void
    {
        $admins = User::role('super_admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Terdapat jurnal tidak seimbang.')
            ->body($entries->count().' jurnal memiliki total debit yang tidak sama dengan kredit. Periksa menu Jurnal Umum.')
            ->sendToDatabase($admins);
    }
}
