<?php

namespace App\Filament\Resources\AssetOpnames;

use App\Filament\Resources\AssetOpnames\Pages\ManageAssetOpnames;
use App\Filament\Resources\AssetOpnames\Pages\ViewAssetOpname;
use App\Filament\Resources\AssetOpnames\RelationManagers\ItemsRelationManager;
use App\Models\AssetOpname;
use App\Services\Assets\AssetOpnameService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class AssetOpnameResource extends Resource
{
    protected static ?string $model = AssetOpname::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Aset & Inventaris';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Opname';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Opname Aset';
    }

    /**
     * Opname (doc 07 §5) — snapshot aset per lokasi; operator_tu conducts,
     * others with asset view can inspect.
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label('Nama Opname'),
            TextEntry::make('opname_date')->label('Tanggal')->date(),
            TextEntry::make('conductedBy.name')->label('Pelaksana'),
            TextEntry::make('notes')->label('Catatan')->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Opname')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('opname_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('conductedBy.name')
                    ->label('Pelaksana'),
                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items'),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
                self::finalizeAction(),
            ])
            ->filters([
                //
            ]);
    }

    /**
     * Finalisasi opname (doc 07 §5): condition updates + hilang disposal
     * JEs for items checked not-found.
     */
    public static function finalizeAction(): Action
    {
        return Action::make('finalisasi')
            ->label('Finalisasi')
            ->icon('heroicon-m-check-badge')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Terapkan hasil opname: perbarui kondisi aset yang ditemukan dan hapuskan (hilang) aset yang tidak ditemukan. Jurnal penghapusan dibukukan untuk aset hilang.')
            ->visible(fn (AssetOpname $record): bool => $record->items()->where('found', false)->exists())
            ->action(function (AssetOpname $record): void {
                try {
                    $result = app(AssetOpnameService::class)->finalize($record, (int) auth()->id());
                } catch (\App\Exceptions\AccountingException $exception) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'finalisasi' => $exception->getMessage(),
                    ]);
                }

                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title('Opname difinalisasi.')
                    ->body("{$result['conditionsUpdated']} kondisi diperbarui, {$result['missingDisposed']} aset hilang dihapuskan.")
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAssetOpnames::route('/'),
            'view' => ViewAssetOpname::route('/{record}'),
        ];
    }
}
