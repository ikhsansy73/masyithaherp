<?php

namespace App\Filament\Resources\InvoiceBatches\Pages;

use App\Enums\FeeCategory;
use App\Exceptions\AccountingException;
use App\Filament\Resources\InvoiceBatches\InvoiceBatchResource;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Services\Billing\InvoiceBatchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Illuminate\Validation\ValidationException;

class ManageInvoiceBatches extends ManageRecords
{
    protected static string $resource = InvoiceBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Batch')
                ->icon('heroicon-m-sparkles')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('billing.batch.create') ?? false)
                ->modalWidth(Width::TwoExtraLarge)
                ->schema([
                    Select::make('academic_year_id')
                        ->label('Tahun Ajaran')
                        ->options(AcademicYear::query()->orderByDesc('starts_at')->pluck('name', 'id'))
                        ->default(fn (): ?int => AcademicYear::query()->where('is_default', true)->value('id'))
                        ->required(),
                    Select::make('fee_type_id')
                        ->label('Jenis Biaya')
                        ->options(fn (): array => FeeType::query()
                            ->where('is_active', true)
                            ->where('category', FeeCategory::Bulanan->value)
                            ->orderBy('code')
                            ->pluck('name', 'id')
                            ->all())
                        ->required()
                        ->live(),
                    Select::make('period_month')
                        ->label('Bulan Tagihan')
                        ->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
                        ->default(fn (): int => (int) now()->addMonth()->month)
                        ->required(),
                    Select::make('grade_filter')
                        ->label('Tingkat (opsional)')
                        ->options(array_combine(range(1, 6), array_map(fn (int $g): string => "Kelas {$g}", range(1, 6))))
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    $year = AcademicYear::query()->findOrFail($data['academic_year_id']);
                    $feeType = FeeType::query()->findOrFail($data['fee_type_id']);

                    try {
                        $batch = app(InvoiceBatchService::class)->generate(
                            year: $year,
                            feeType: $feeType,
                            periodMonth: (int) $data['period_month'],
                            gradeFilter: isset($data['grade_filter']) ? (int) $data['grade_filter'] : null,
                            actor: auth()->user(),
                        );

                        if ($batch === null) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Tidak ada siswa eligible untuk batch ini.')
                                ->send();

                            return;
                        }

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title("Batch {$feeType->code} dibuat: {$batch->total_invoices} tagihan, total Rp ".number_format($batch->total_amount, 0, ',', '.'))
                            ->send();
                    } catch (AccountingException $exception) {
                        throw ValidationException::withMessages([
                            'period_month' => $exception->getMessage(),
                        ]);
                    }
                }),
        ];
    }
}
