<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Models\Location;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Aset & Inventaris';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Lokasi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lokasi Aset';
    }

    /**
     * Lokasi aset is asset master data — same gate as the registry
     * (operator_tu manages, kepala_sekolah/bendahara view).
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'assets.asset.view',
            'create', 'update', 'replicate', 'reorder' => 'assets.asset.update',
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
        return $schema->components([
            TextInput::make('code')
                ->label('Kode')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->label('Nama Ruang/Lokasi')
                ->required()
                ->maxLength(100),
            TextInput::make('building')
                ->label('Gedung')
                ->maxLength(100),
            Select::make('parent_id')
                ->label('Induk')
                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->native(false),
        ]);
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
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('building')
                    ->label('Gedung')
                    ->placeholder('—'),
                TextColumn::make('parent.name')
                    ->label('Induk')
                    ->placeholder('—'),
                TextColumn::make('assets_count')
                    ->label('Jumlah Aset')
                    ->counts('assets'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Location $record): bool => ! $record->assets()->exists()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLocations::route('/'),
        ];
    }
}
