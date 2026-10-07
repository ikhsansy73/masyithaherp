<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Enums\JournalSource;
use App\Exceptions\AccountingException;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManageJournalEntries extends ManageRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Jurnal')
                ->using(function (array $data): JournalEntry {
                    $lines = [];

                    foreach ($data['lines'] as $row) {
                        $debit = (int) ($row['debit'] ?? 0);
                        $credit = (int) ($row['credit'] ?? 0);

                        if ($debit === 0 && $credit === 0) {
                            continue;
                        }

                        $lines[] = new JournalDraftLine(
                            accountId: (int) $row['account_id'],
                            debit: $debit,
                            credit: $credit,
                            fundId: isset($row['fund_id']) && $row['fund_id'] !== '' ? (int) $row['fund_id'] : null,
                            memo: $row['memo'] ?? null,
                        );
                    }

                    try {
                        return app(JournalPostingService::class)->post(new JournalDraft(
                            entryDate: Carbon::parse($data['entry_date']),
                            description: $data['description'],
                            userId: auth()->id(),
                            lines: $lines,
                            source: JournalSource::Manual,
                        ));
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages([
                            'description' => $exception->getMessage(),
                        ]);
                    }
                }),
        ];
    }
}
