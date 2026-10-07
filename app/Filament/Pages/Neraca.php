<?php

namespace App\Filament\Pages;

use App\Services\Accounting\Reports\BalanceSheet;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;

class Neraca extends AccountingReportPage
{
    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Neraca (Posisi Keuangan)';

    protected static ?string $title = 'Neraca (Posisi Keuangan)';

    protected string $view = 'filament.pages.neraca';

    protected function filterGrid(): Grid
    {
        return Grid::make(4)
            ->schema([
                DatePicker::make('as_of')
                    ->label('Per Tanggal')
                    ->required(),
                Select::make('fund_id')
                    ->label('Dana')
                    ->options($this->fundOptions())
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public function report(): ?array
    {
        if (blank($this->data['as_of'] ?? null)) {
            return null;
        }

        return $this->runReport(fn (): array => app(BalanceSheet::class)->generate(
            Carbon::parse($this->data['as_of']),
            $this->resolveFundId(),
        ));
    }
}
