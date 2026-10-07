<?php

namespace App\Filament\Pages;

use App\Enums\PeriodStatus;
use App\Exceptions\AccountingException;
use App\Models\AccountingPeriod;
use App\Services\Accounting\AccountingPeriodService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

class PeriodeAkuntansi extends Page implements \Filament\Tables\Contracts\HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Periode Akuntansi';

    protected static ?string $title = 'Periode Akuntansi';

    protected string $view = 'filament.pages.periode-akuntansi';

    public function mount(): void {}

    public static function canAccess(): bool
    {
        return auth()->user()?->can('accounting.period.viewAny') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AccountingPeriod::query()->orderBy('starts_at'))
            ->columns([
                TextColumn::make('name')
                    ->label('Periode')
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran'),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->date(),
                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->date(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PeriodStatus $state): string => $state->label())
                    ->color(fn (PeriodStatus $state): array => $state === PeriodStatus::Closed
                        ? Color::Rose
                        : Color::Green),
                TextColumn::make('closedBy.name')
                    ->label('Ditutup Oleh')
                    ->placeholder('—'),
                TextColumn::make('closed_at')
                    ->label('Waktu Penutupan')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->recordActions([
                self::tutupBukuAction(),
                self::bukaKembaliAction(),
            ])
            ->paginated(false);
    }

    /**
     * Tutup buku: posts the closing entry (rule #17) and locks the period.
     */
    private static function tutupBukuAction(): Action
    {
        return Action::make('tutup-buku')
            ->label('Tutup Buku')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Tutup buku akan memindahkan seluruh saldo pendapatan dan beban periode ini '
                .'ke akun Surplus/Defisit (3-1200), lalu mengunci periode. Lanjutkan?')
            ->visible(fn (AccountingPeriod $record): bool => $record->status === PeriodStatus::Open
                && (auth()->user()?->can('accounting.period.update') ?? false))
            ->action(function (AccountingPeriod $record): void {
                try {
                    app(AccountingPeriodService::class)->close($record, (int) auth()->id());

                    Notification::make()
                        ->success()
                        ->title('Periode '.$record->name.' telah ditutup.')
                        ->body('Jurnal penutup telah diposting dan periode dikunci.')
                        ->send();
                } catch (AccountingException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Periode tidak dapat ditutup.')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    /**
     * Buka kembali: reopen a closed period (super_admin only).
     */
    private static function bukaKembaliAction(): Action
    {
        return Action::make('buka-kembali')
            ->label('Buka Kembali')
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Membuka kembali periode akan membatalkan jurnal penutup '
                .'(jurnal balikan otomatis) dan membuka periode. Lanjutkan?')
            ->visible(fn (AccountingPeriod $record): bool => $record->status === PeriodStatus::Closed
                && (auth()->user()?->can('accounting.period.reopen') ?? false))
            ->action(function (AccountingPeriod $record): void {
                try {
                    app(AccountingPeriodService::class)->reopen($record, (int) auth()->id());

                    Notification::make()
                        ->success()
                        ->title('Periode '.$record->name.' dibuka kembali.')
                        ->send();
                } catch (AccountingException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Periode tidak dapat dibuka.')
                        ->body($exception->getMessage())
                        ->send();
                }
            });
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }
}
