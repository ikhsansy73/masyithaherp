<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Services\Accounting\Reports\GeneralLedger;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Carbon;

class BukuBesar extends AccountingReportPage
{
    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Buku Besar';

    protected static ?string $title = 'Buku Besar';

    protected string $view = 'filament.pages.buku-besar';

    protected function filterGrid(): Grid
    {
        return Grid::make(4)
            ->schema([
                Select::make('account_id')
                    ->label('Akun')
                    ->options(self::accountOptions())
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('from')
                    ->label('Dari Tanggal')
                    ->required(),
                DatePicker::make('to')
                    ->label('Sampai Tanggal')
                    ->required(),
                Select::make('fund_id')
                    ->label('Dana')
                    ->options($this->fundOptions())
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    /**
     * Postable accounts formatted "kode — nama".
     *
     * @return array<int, string>
     */
    private static function accountOptions(): array
    {
        return Account::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $account): array => [
                $account->getKey() => $account->code.' — '.$account->name,
            ])
            ->all();
    }

    public function report(): ?array
    {
        $account = Account::query()->find($this->data['account_id'] ?? null);

        if ($account === null) {
            return null;
        }

        if (blank($this->data['from'] ?? null) || blank($this->data['to'] ?? null)) {
            return null;
        }

        return $this->runReport(fn (): array => app(GeneralLedger::class)->generate(
            $account,
            Carbon::parse($this->data['from']),
            Carbon::parse($this->data['to']),
            $this->resolveFundId(),
        ));
    }
}
