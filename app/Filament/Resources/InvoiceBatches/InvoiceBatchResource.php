<?php

namespace App\Filament\Resources\InvoiceBatches;

use App\Enums\InvoiceBatchStatus;
use App\Filament\Resources\InvoiceBatches\Pages\ManageInvoiceBatches;
use App\Models\InvoiceBatch;
use App\Services\Billing\InvoiceBatchService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class InvoiceBatchResource extends Resource
{
    protected static ?string $model = InvoiceBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 8;

    public static function getModelLabel(): string
    {
        return 'Batch Tagihan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Batch Tagihan';
    }

    /**
     * billing.batch — bendahara only (doc 09). Void additionally needs
     * billing.void.
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
            'viewAny', 'view' => 'billing.batch.view',
            'create', 'update', 'replicate', 'reorder' => 'billing.batch.update',
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny', 'restore', 'restoreAny' => 'billing.batch.delete',
            default => null,
        };

        if ($permission === null) {
            return parent::getAuthorizationResponse($action, $record);
        }

        if ($record !== null && $ability === 'delete' && $record->status !== InvoiceBatchStatus::Draft) {
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
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('feeType.name')
                    ->label('Jenis Biaya')
                    ->searchable(),
                TextColumn::make('academicYear.name')
                    ->label('Tahun')
                    ->badge(),
                TextColumn::make('period')
                    ->label('Periode')
                    ->badge()
                    ->state(fn (InvoiceBatch $record): string => $record->period_month !== null
                        ? \App\Services\Billing\InvoiceService::monthLabel($record->academicYear, $record->period_month)
                        : 'Sekali'),
                TextColumn::make('grade_filter')
                    ->label('Tingkat')
                    ->placeholder('Semua'),
                TextColumn::make('total_invoices')
                    ->label('Tagihan')
                    ->numeric(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('semibold'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceBatchStatus $state): string => $state->label())
                    ->color(fn (InvoiceBatchStatus $state): array => match ($state) {
                        InvoiceBatchStatus::Draft => Color::Gray,
                        InvoiceBatchStatus::Issued => Color::Blue,
                        InvoiceBatchStatus::Void => Color::Rose,
                    }),
                TextColumn::make('generatedBy.name')
                    ->label('Oleh')
                    ->placeholder('—'),
                TextColumn::make('generated_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('terbitkan')
                    ->label('Terbitkan')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (InvoiceBatch $record): bool => $record->status === InvoiceBatchStatus::Draft)
                    ->action(function (InvoiceBatch $record): void {
                        app(InvoiceBatchService::class)->issue($record, auth()->user());
                    }),
                Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (InvoiceBatch $record): bool => $record->status === InvoiceBatchStatus::Issued)
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(5),
                    ])
                    ->action(function (InvoiceBatch $record, array $data): void {
                        app(InvoiceBatchService::class)->void($record, $data['reason'], auth()->user());
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', direction: 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInvoiceBatches::route('/'),
        ];
    }
}
