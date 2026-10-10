<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayrollService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;
use Illuminate\Validation\ValidationException;

class ManagePayrollPeriods extends ManageRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buat')
                ->label('Buat Payroll')
                ->icon('heroicon-m-plus')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('payroll.calculate') ?? false)
                ->modalWidth(Width::TwoExtraLarge)
                ->schema([
                    TextInput::make('period_year')
                        ->label('Tahun')
                        ->numeric()
                        ->integer()
                        ->minValue(2020)
                        ->maxValue(2100)
                        ->default(now()->year)
                        ->required(),
                    Select::make('period_month')
                        ->label('Bulan')
                        ->options(static::monthOptions())
                        ->default(fn (): int => (int) now()->month)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $period = app(PayrollService::class)->create(
                            year: (int) $data['period_year'],
                            month: (int) $data['period_month'],
                            actor: auth()->user(),
                        );

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Payroll '.$period->name.' dibuat dengan '.PayrollPeriod::query()
                                ->whereKey($period->getKey())->withCount('payslips')->value('payslips_count').' slip')
                            ->send();
                    } catch (\App\Exceptions\AccountingException $exception) {
                        throw ValidationException::withMessages([
                            'period_month' => $exception->getMessage(),
                        ]);
                    }
                }),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function monthOptions(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }
}
