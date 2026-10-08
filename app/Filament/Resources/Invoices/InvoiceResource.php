<?php

namespace App\Filament\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 9;

    public static function getModelLabel(): string
    {
        return 'Tagihan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tagihan';
    }

    /**
     * billing.invoice — bendahara manages, kepala_sekolah/operator_tu/
     * wali_murid view (doc 09).
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
            'viewAny', 'view' => 'billing.invoice.view',
            'create', 'update', 'replicate', 'reorder' => 'billing.invoice.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'billing.invoice.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        if ($record !== null && $ability === 'delete' && $record->paid_amount > 0) {
            return Response::deny();
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
                    ->label('No. Tagihan')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('student.full_name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_month')
                    ->label('Bulan')
                    ->placeholder('—'),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->money('IDR'),
                TextColumn::make('remaining')
                    ->label('Sisa')
                    ->state(fn (Invoice $record): string => 'Rp '.number_format($record->remainingAmount(), 0, ',', '.'))
                    ->color('danger'),
                TextColumn::make('fund.name')
                    ->label('Dana')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label())
                    ->color(fn (InvoiceStatus $state): array => match ($state) {
                        InvoiceStatus::Draft => Color::Gray,
                        InvoiceStatus::Issued => Color::Blue,
                        InvoiceStatus::PartiallyPaid => Color::Amber,
                        InvoiceStatus::Paid => Color::Green,
                        InvoiceStatus::Void => Color::Rose,
                        InvoiceStatus::Cancelled => Color::Rose,
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record): bool => $record->status->isPayable() && $record->paid_amount === 0)
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(5),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        app(\App\Services\Billing\InvoiceService::class)
                            ->voidInvoice($record, $data['reason'], auth()->user());
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date')
            ->defaultSort('id', direction: 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Infolists\Components\TextEntry::make('number')
                ->label('No. Tagihan')
                ->weight('semibold'),
            \Filament\Infolists\Components\TextEntry::make('student.full_name')
                ->label('Siswa'),
            \Filament\Infolists\Components\TextEntry::make('period_month')
                ->label('Bulan')
                ->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('due_date')
                ->label('Jatuh Tempo')
                ->date('d M Y'),
            \Filament\Infolists\Components\TextEntry::make('total')
                ->label('Total')
                ->money('IDR')
                ->weight('semibold'),
            \Filament\Infolists\Components\TextEntry::make('paid_amount')
                ->label('Dibayar')
                ->money('IDR'),
            \Filament\Infolists\Components\TextEntry::make('remaining')
                ->label('Sisa')
                ->state(fn (Invoice $record): string => 'Rp '.number_format($record->remainingAmount(), 0, ',', '.')),
            \Filament\Infolists\Components\TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (InvoiceStatus $state): string => $state->label()),
            \Filament\Infolists\Components\TextEntry::make('voided_reason')
                ->label('Alasan Pembatalan')
                ->placeholder('-')
                ->columnSpanFull(),
            \Filament\Infolists\Components\RepeatableEntry::make('items')
                ->label('Rincian')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('item_type')
                        ->label('Jenis'),
                    \Filament\Infolists\Components\TextEntry::make('description')
                        ->label('Keterangan'),
                    \Filament\Infolists\Components\TextEntry::make('amount')
                        ->label('Nominal')
                        ->money('IDR'),
                ]),
            \Filament\Infolists\Components\RepeatableEntry::make('allocations')
                ->label('Pembayaran')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('payment.number')
                        ->label('Kwitansi'),
                    \Filament\Infolists\Components\TextEntry::make('payment.payment_date')
                        ->label('Tanggal')
                        ->date('d M Y'),
                    \Filament\Infolists\Components\TextEntry::make('amount')
                        ->label('Alokasi')
                        ->money('IDR'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }
}
