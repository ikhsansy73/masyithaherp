<?php

namespace App\Filament\Resources\InventoryItems;

use App\Filament\Resources\InventoryItems\Pages\ManageInventoryItems;
use App\Filament\Resources\InventoryItems\Pages\ViewInventoryItem;
use App\Filament\Resources\InventoryItems\RelationManagers\MovementsRelationManager;
use App\Models\InventoryItem;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Aset & Inventaris';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return 'Item ATK';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ATK & Persediaan';
    }

    /**
     * ATK inventory (doc 07 §6) — operator_tu manages stock, others with
     * asset view can inspect.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'assets.asset.view',
            'create', 'update', 'reorder', 'replicate' => 'assets.asset.update',
            default => 'assets.asset.delete',
        };

        return auth()->user()?->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('name')
                    ->label('Nama Item')
                    ->searchable(),
                TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('current_stock')
                    ->label('Stok')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('min_stock')
                    ->label('Min. Stok')
                    ->numeric(2),
                TextColumn::make('avg_cost')
                    ->label('Harga Rata-rata')
                    ->money('IDR'),
                TextColumn::make('stock_value')
                    ->label('Nilai Stok')
                    ->state(fn (InventoryItem $record): string => 'Rp '.number_format($record->stockValue(), 0, ',', '.')),
                TextColumn::make('low_stock')
                    ->label('Status')
                    ->badge()
                    ->state(fn (InventoryItem $record): string => $record->isLowStock() ? 'Stok Menipis' : 'Aman')
                    ->color(fn (InventoryItem $record): string => $record->isLowStock() ? 'danger' : 'success'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInventoryItems::route('/'),
            'view' => ViewInventoryItem::route('/{record}'),
        ];
    }
}
