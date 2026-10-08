<?php

namespace App\Filament\Pages;

use App\Models\Classroom;
use App\Services\Billing\ArrearsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class Tunggakan extends Page
{
    protected string $view = 'filament.pages.tunggakan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Laporan Tunggakan';

    protected static ?string $title = 'Laporan Tunggakan';

    protected static ?int $navigationSort = 11;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('billing.arrears.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kelas')
                    ->label('Kelas')
                    ->options(Classroom::query()->orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('bulan')
                    ->label('Bulan Tagihan')
                    ->options([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'])
                    ->nullable(),
                Select::make('beasiswa')
                    ->label('Status Beasiswa')
                    ->options([
                        '1' => 'Penerima potongan saja',
                        '0' => 'Tanpa potongan saja',
                    ])
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('filter-form')
                    ->livewireSubmitHandler('filter')
                    ->footer([
                        Actions::make([
                            Action::make('filter')
                                ->label('Tampilkan')
                                ->submit('filter'),
                        ]),
                    ]),
            ]);
    }

    public function filter(): void
    {
        $this->form->getState();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak PDF')
                ->icon('heroicon-m-printer')
                ->url(fn (): string => route('billing.daftar-tunggakan', array_filter([
                    'kelas' => $this->data['kelas'] ?? null,
                    'bulan' => $this->data['bulan'] ?? null,
                    'beasiswa' => $this->data['beasiswa'] ?? null,
                ])), shouldOpenInNewTab: true),
        ];
    }

    /**
     * Arrears rows after applying the page filters.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $rows = app(ArrearsService::class)->arrears();

        if (filled($this->data['kelas'] ?? null)) {
            $rows = $rows->filter(fn (array $row): bool => $row['classroom'] === $this->data['kelas']);
        }

        if (filled($this->data['bulan'] ?? null)) {
            $month = (int) $this->data['bulan'];
            $rows = $rows->filter(function (array $row) use ($month): bool {
                $months = \App\Models\Invoice::query()
                    ->whereKey($row['invoice_ids'])
                    ->pluck('period_month');

                return $months->map(fn ($value): int => (int) $value)->contains($month);
            });
        }

        if (filled($this->data['beasiswa'] ?? null)) {
            $onlyDiscounted = $this->data['beasiswa'] === '1';
            $rows = $rows->filter(fn (array $row): bool => $row['has_discount'] === $onlyDiscounted);
        }

        return $rows->values();
    }

    /**
     * Payments with unallocated remainders (doc 04 §4 widget).
     *
     * @return Collection<int, \App\Models\Payment>
     */
    public function unallocated(): Collection
    {
        return app(ArrearsService::class)->unallocatedPayments();
    }
}
