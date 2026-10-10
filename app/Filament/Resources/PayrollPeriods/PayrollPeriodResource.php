<?php

namespace App\Filament\Resources\PayrollPeriods;

use App\Enums\PayrollStatus;
use App\Filament\Resources\PayrollPeriods\Pages\ManagePayrollPeriods;
use App\Filament\Resources\PayrollPeriods\Pages\ViewPayrollPeriod;
use App\Models\PayrollPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriodResource extends Resource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM & Penggajian';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Periode Payroll';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Payroll';
    }

    /**
     * The payroll resource is read-only through CRUD verbs — the lifecycle
     * runs through service actions gated by payroll.calculate/approve/pay.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        if (in_array($ability, ['viewAny', 'view'], true)) {
            return auth()->user()?->can('payroll.view')
                ? Response::allow()
                : Response::deny();
        }

        return Response::deny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            //
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Infolists\Components\TextEntry::make('name')
                ->label('Periode')
                ->weight('semibold'),
            \Filament\Infolists\Components\TextEntry::make('month')
                ->label('Bulan')
                ->state(fn (PayrollPeriod $record): string => $record->monthLabel()),
            \Filament\Infolists\Components\TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (PayrollStatus $state): string => $state->label())
                ->color(fn (PayrollStatus $state): array => static::statusColor($state)),
            \Filament\Infolists\Components\TextEntry::make('total_gross')
                ->label('Total Bruto')
                ->money('IDR'),
            \Filament\Infolists\Components\TextEntry::make('total_deductions')
                ->label('Total Potongan')
                ->money('IDR'),
            \Filament\Infolists\Components\TextEntry::make('total_net')
                ->label('Total Neto')
                ->money('IDR')
                ->weight('semibold'),
            \Filament\Infolists\Components\TextEntry::make('calculated_at')
                ->label('Dihitung')
                ->dateTime('d M Y H:i')
                ->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('approvedBy.name')
                ->label('Disetujui Oleh')
                ->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('approved_at')
                ->label('Disetujui')
                ->dateTime('d M Y H:i')
                ->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('paid_at')
                ->label('Dibayar')
                ->dateTime('d M Y H:i')
                ->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('journal')
                ->label('Jurnal Akrual')
                ->state(fn (PayrollPeriod $record): string => $record->journalEntry?->number ?? '-'),
            \Filament\Infolists\Components\TextEntry::make('payment_journal')
                ->label('Jurnal Pembayaran')
                ->state(fn (PayrollPeriod $record): string => $record->paymentJournalEntry?->number ?? '-'),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Periode')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PayrollStatus $state): string => $state->label())
                    ->color(fn (PayrollStatus $state): array => static::statusColor($state)),
                TextColumn::make('payslips_count')
                    ->label('Slip')
                    ->counts('payslips'),
                TextColumn::make('total_gross')
                    ->label('Bruto')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_deductions')
                    ->label('Potongan')
                    ->money('IDR'),
                TextColumn::make('total_net')
                    ->label('Neto')
                    ->money('IDR')
                    ->weight('semibold')
                    ->sortable(),
                TextColumn::make('approvedBy.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-'),
                TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('d M Y')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->defaultSort('id', direction: 'desc');
    }

    public static function statusColor(PayrollStatus $state): array
    {
        return match ($state->color()) {
            'gray' => Color::Gray,
            'info' => Color::Blue,
            'warning' => Color::Amber,
            'success' => Color::Emerald,
            'danger' => Color::Rose,
            default => Color::Gray,
        };
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PayslipsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayrollPeriods::route('/'),
            'view' => ViewPayrollPeriod::route('/{record}'),
        ];
    }
}
