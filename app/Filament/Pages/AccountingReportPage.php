<?php

namespace App\Filament\Pages;

use App\Exceptions\AccountingException;
use App\Models\Fund;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

abstract class AccountingReportPage extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->can('accounting.report.view') ?? false;
    }

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public ?string $reportError = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill($this->defaultFilters());
    }

    /**
     * Initial filter values: the current month, all funds.
     *
     * @return array<string, mixed>
     */
    protected function defaultFilters(): array
    {
        return [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
            'fund_id' => null,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->filterGrid(),
            ])
            ->statePath('data');
    }

    /**
     * Filter fields of the statement (each child defines its own grid).
     */
    abstract protected function filterGrid(): Grid;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('filter')
                    ->footer([
                        Actions::make([
                            Action::make('filter')
                                ->label('Tampilkan')
                                ->submit('filter')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function filter(): void
    {
        $this->form->getState();
    }

    /**
     * Run the report, converting an unbalanced-ledger exception into a
     * page-level error message instead of an HTTP 500.
     */
    protected function runReport(\Closure $callback): ?array
    {
        $this->reportError = null;

        try {
            return $callback();
        } catch (AccountingException $exception) {
            $this->reportError = $exception->getMessage();

            return null;
        }
    }

    /**
     * The selected fund filter, or null for all funds.
     */
    protected function resolveFundId(): ?int
    {
        $fundId = $this->data['fund_id'] ?? null;

        return filled($fundId) ? (int) $fundId : null;
    }

    /**
     * Fund options for filter selects: all funds formatted "KODE — nama".
     */
    protected function fundOptions(): array
    {
        return Fund::query()->orderBy('code')->get()
            ->mapWithKeys(fn (Fund $fund): array => [$fund->getKey() => $fund->code.' — '.$fund->name])
            ->all();
    }

    /**
     * Shared period + fund filter grid used by most statements.
     */
    protected function periodFundGrid(): Grid
    {
        return Grid::make(4)
            ->schema([
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

    abstract public function report(): ?array;
}
