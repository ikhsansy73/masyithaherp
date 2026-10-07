<?php

namespace App\Filament\Pages;

use App\Services\Accounting\Reports\CashFlow;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;

class ArusKas extends AccountingReportPage
{
    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Arus Kas';

    protected static ?string $title = 'Arus Kas';

    protected string $view = 'filament.pages.arus-kas';

    protected function filterGrid(): Grid
    {
        return $this->periodFundGrid();
    }

    public function report(): ?array
    {
        if (blank($this->data['from'] ?? null) || blank($this->data['to'] ?? null)) {
            return null;
        }

        return $this->runReport(fn (): array => app(CashFlow::class)->generate(
            Carbon::parse($this->data['from']),
            Carbon::parse($this->data['to']),
            $this->resolveFundId(),
        ));
    }
}
