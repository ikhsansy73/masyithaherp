<?php

namespace App\Filament\Pages;

use App\Services\Accounting\Reports\TrialBalance;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;

class NeracaSaldo extends AccountingReportPage
{
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Neraca Saldo';

    protected static ?string $title = 'Neraca Saldo';

    protected string $view = 'filament.pages.neraca-saldo';

    protected function filterGrid(): Grid
    {
        return $this->periodFundGrid();
    }

    public function report(): ?array
    {
        if (blank($this->data['from'] ?? null) || blank($this->data['to'] ?? null)) {
            return null;
        }

        return $this->runReport(fn (): array => app(TrialBalance::class)->generate(
            Carbon::parse($this->data['from']),
            Carbon::parse($this->data['to']),
            $this->resolveFundId(),
        ));
    }
}
