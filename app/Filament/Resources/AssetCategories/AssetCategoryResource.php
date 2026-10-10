<?php

namespace App\Filament\Resources\AssetCategories;

use App\Filament\Resources\AssetCategories\Pages\ManageAssetCategories;
use App\Models\Account;
use App\Models\AssetCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class AssetCategoryResource extends Resource
{
    protected static ?string $model = AssetCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Aset & Inventaris';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Kelompok Aset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kelompok Aset';
    }

    /**
     * Master data aset — gate behind the asset registry permissions
     * (doc 09): operator_tu manages, kepala_sekolah/bendahara view.
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
                ->maxLength(10)
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->label('Nama Kelompok')
                ->required()
                ->maxLength(100),
            TextInput::make('useful_life_months')
                ->label('Masa Manfaat (bulan)')
                ->numeric()
                ->minValue(1)
                ->maxValue(600),
            Select::make('asset_account_id')
                ->label('Akun Aset (1-2xxx)')
                ->options(fn (): array => Account::query()
                    ->where('code', 'like', '1-2%')
                    ->where('is_header', false)
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->required(),
            Toggle::make('is_depreciable')
                ->label('Disusutkan')
                ->default(true)
                ->live()
                ->helperText('Nonaktif untuk tanah/buku — asetnya tidak masuk run penyusutan.'),
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
                TextColumn::make('assetAccount.code')
                    ->label('Akun Aset')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('useful_life_months')
                    ->label('Masa Manfaat')
                    ->formatStateUsing(fn ($state): string => $state !== null ? $state.' bln' : '—'),
                IconColumn::make('is_depreciable')
                    ->label('Disusutkan')
                    ->boolean(),
                TextColumn::make('assets_count')
                    ->label('Jumlah Unit')
                    ->counts('assets'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (AssetCategory $record): bool => ! $record->assets()->exists()),
            ])
            ->filters([
                //
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
            'index' => ManageAssetCategories::route('/'),
        ];
    }
}
