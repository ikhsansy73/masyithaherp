<?php

namespace App\Filament\Resources\Assets;

use App\Enums\AssetStatus;
use App\Exceptions\AccountingException;
use App\Filament\Resources\Assets\Pages\ManageAssets;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Models\Asset;
use App\Services\Assets\AssetService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Aset & Inventaris';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'Aset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Daftar Aset';
    }

    /**
     * Daftar aset — read-only registry (doc 07 §1): writes only via
     * Catat/Duplikat/Penghapusan actions. operator_tu manages,
     * kepala_sekolah/bendahara view.
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

    /**
     * Duplikat aset (doc 07 §1): clones a unit row with a fresh code and
     * posts its own JE #12 — for bulk purchases of identical units.
     */
    public static function duplicateAction(): Action
    {
        return Action::make('duplikat')
            ->label('Duplikat')
            ->icon('heroicon-m-document-duplicate')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('Buat salinan unit aset ini dengan kode inventaris baru? Satu jurnal akuisisi baru akan dibukukan.')
            ->visible(fn (Asset $record): bool => $record->status === AssetStatus::Aktif
                && (auth()->user()?->can('assets.asset.create') ?? false))
            ->action(function (Asset $record): void {
                try {
                    $duplicate = app(AssetService::class)->duplicate($record, (int) auth()->id());
                } catch (AccountingException $exception) {
                    throw ValidationException::withMessages([
                        'code' => $exception->getMessage(),
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title("Aset diduplikat menjadi {$duplicate->code}")
                    ->send();
            });
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Aset')
                ->schema([
                    TextEntry::make('code')
                        ->label('Kode Inventaris'),
                    TextEntry::make('name')
                        ->label('Nama Aset'),
                    TextEntry::make('category.name')
                        ->label('Kelompok'),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('condition')
                        ->label('Kondisi')
                        ->badge(),
                ])
                ->columns(2),
            Section::make('Perolehan')
                ->schema([
                    TextEntry::make('acquisition_date')
                        ->label('Tanggal Perolehan')
                        ->date(),
                    TextEntry::make('acquisition_cost')
                        ->label('Biaya Perolehan')
                        ->money('IDR'),
                    TextEntry::make('fund.name')
                        ->label('Sumber Dana (fund)'),
                    TextEntry::make('funding_source')
                        ->label('Sumber Dana'),
                    TextEntry::make('brand_model')
                        ->label('Merk/Tipe')
                        ->placeholder('—'),
                    TextEntry::make('serial_no')
                        ->label('No. Seri')
                        ->placeholder('—'),
                ])
                ->columns(2),
            Section::make('Akuntansi & Penyusutan')
                ->schema([
                    TextEntry::make('journalEntry.number')
                        ->label('Jurnal Akuisisi')
                        ->placeholder('—')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('disposalJournalEntry.number')
                        ->label('Jurnal Penghapusan')
                        ->placeholder('—')
                        ->badge()
                        ->color('danger'),
                    TextEntry::make('useful_life_months')
                        ->label('Masa Manfaat')
                        ->formatStateUsing(fn ($state): string => $state !== null ? $state.' bln' : '—')
                        ->suffix(fn (Asset $record): string => $record->useful_life_months === null
                            && $record->category?->useful_life_months !== null ? ' (default kelompok)' : ''),
                    TextEntry::make('salvage_value')
                        ->label('Nilai Sisa')
                        ->money('IDR'),
                    TextEntry::make('net_book_value')
                        ->label('Nilai Buku')
                        ->state(fn (Asset $record): string => 'Rp '.number_format($record->netBookValue(), 0, ',', '.')),
                    TextEntry::make('accumulated_depreciation')
                        ->label('Akumulasi Penyusutan')
                        ->state(fn (Asset $record): string => 'Rp '.number_format($record->accumulatedDepreciation(), 0, ',', '.')),
                ])
                ->columns(2)
                ->visibleOn('view'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Inventaris')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kelompok')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('acquisition_date')
                    ->label('Tgl Perolehan')
                    ->date()
                    ->sortable(),
                TextColumn::make('acquisition_cost')
                    ->label('Biaya Perolehan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->placeholder('—'),
                TextColumn::make('custodian.name')
                    ->label('Penanggung Jawab')
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
                self::duplicateAction(),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAssets::route('/'),
            'view' => ViewAsset::route('/{record}'),
        ];
    }
}
