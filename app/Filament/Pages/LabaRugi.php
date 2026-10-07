<?php

namespace App\Filament\Pages;

use App\Services\Accounting\Reports\IncomeStatement;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;

class LabaRugi extends AccountingReportPage
{
    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?string $title = 'Laba Rugi';

    protected string $view = 'filament.pages.laba-rugi';

    protected function filterGrid(): Grid
    {
        return $this->periodFundGrid();
    }

    public function report(): ?array
    {
        if (blank($this->data['from'] ?? null) || blank($this->data['to'] ?? null)) {
            return null;
        }

        return $this->runReport(fn (): array => app(IncomeStatement::class)->generate(
            Carbon::parse($this->data['from']),
            Carbon::parse($this->data['to']),
            $this->resolveFundId(),
        ));
    }
}
