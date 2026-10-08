<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentMethod;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return 'Pembayaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pembayaran (Kwitansi)';
    }

    /**
     * billing.payment — bendahara & operator_tu record; kepala_sekolah
     * and wali_murid view (doc 09). Void needs billing.void.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        if ($ability === 'void') {
            return auth()->user()?->can('billing.void')
                ? Response::allow()
                : Response::deny();
        }

        $permission = match ($ability) {
            'viewAny', 'view' => 'billing.payment.view',
            'create', 'update', 'replicate', 'reorder' => 'billing.payment.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'billing.payment.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        return auth()->user()?->can($permission)
            ? Response::allow()
            : Response::deny();
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Kwitansi')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Siswa')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->weight('semibold')
                    ->color(fn (Payment $record): array => $record->amount < 0 ? Color::Rose : Color::Gray),
                TextColumn::make('method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextColumn::make('cashAccount.name')
                    ->label('Kas/Bank'),
                TextColumn::make('reversed')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Payment $record): string => $record->reversed_by_payment_id !== null
                        ? ($record->amount < 0 ? 'Pembatalan' : 'Dibatalkan')
                        : 'Berlaku')
                    ->color(fn (string $state): array => match ($state) {
                        'Berlaku' => Color::Green,
                        'Pembatalan' => Color::Rose,
                        default => Color::Rose,
                    }),
            ])
            ->recordActions([
                Action::make('kwitansi')
                    ->label('Cetak Kwitansi')
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->visible(fn (Payment $record): bool => $record->amount > 0 && $record->journal_entry_id !== null)
                    ->url(fn (Payment $record): string => route('billing.kwitansi', $record), shouldOpenInNewTab: true),
                Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record): bool => $record->amount > 0 && $record->reversed_by_payment_id === null)
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(5),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        app(\App\Services\Billing\PaymentService::class)
                            ->void($record, $data['reason'], auth()->user());
                    }),
            ])
            ->defaultSort('id', direction: 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayments::route('/'),
        ];
    }
}
