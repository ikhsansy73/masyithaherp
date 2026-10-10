<?php

namespace App\Filament\Resources\AssetMaintenances;

use App\Filament\Resources\AssetMaintenances\Pages\ManageAssetMaintenances;
use App\Models\AssetMaintenance;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenanceResource extends Resource
{
    protected static ?string $model = AssetMaintenance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|\UnitEnum|null $navigationGroup = 'Aset & Inventaris';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Perawatan Aset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Perawatan & Perbaikan';
    }

    /**
     * Perawatan aset — writes post beban 5-1800 via the service, so the
     * registry update permission gates them (doc 07 §4).
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'assets.asset.view',
            'create' => 'assets.asset.update',
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.code')
                    ->label('Kode Aset')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('asset.name')
                    ->label('Aset')
                    ->searchable(),
                TextColumn::make('maintenance_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Uraian')
                    ->limit(50),
                TextColumn::make('cost')
                    ->label('Biaya')
                    ->money('IDR'),
                TextColumn::make('vendor')
                    ->label('Vendor')
                    ->placeholder('—'),
                TextColumn::make('journalEntry.number')
                    ->label('Jurnal')
                    ->placeholder('catatan saja')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAssetMaintenances::route('/'),
        ];
    }
}
