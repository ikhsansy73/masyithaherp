<?php

namespace App\Filament\Resources\AssetOpnames\RelationManagers;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\AssetOpnameItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('asset'))
            ->columns([
                TextColumn::make('asset.code')
                    ->label('Kode')
                    ->sortable(),
                TextColumn::make('asset.name')
                    ->label('Nama Aset'),
                TextColumn::make('asset.location.name')
                    ->label('Lokasi')
                    ->placeholder('-'),
                IconColumn::make('found')
                    ->label('Ditemukan')
                    ->boolean(),
                TextColumn::make('condition')
                    ->label('Kondisi (hasil opname)')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AssetCondition ? $state->label() : '-'),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('periksa')
                    ->label('Periksa')
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (AssetOpnameItem $record): bool => $record->asset->status === AssetStatus::Aktif)
                    ->schema([
                        Toggle::make('found')
                            ->label('Ditemukan')
                            ->default(fn (AssetOpnameItem $record): bool => $record->found)
                            ->inline(false),
                        Select::make('condition')
                            ->label('Kondisi')
                            ->options(collect(AssetCondition::cases())->mapWithKeys(
                                fn (AssetCondition $condition): array => [$condition->value => $condition->label()],
                            )->all())
                            ->default(fn (AssetOpnameItem $record): ?string => $record->condition?->value),
                        TextInput::make('notes')
                            ->label('Catatan')
                            ->maxLength(200),
                    ])
                    ->action(function (array $data, AssetOpnameItem $record): void {
                        $record->update([
                            'found' => (bool) ($data['found'] ?? true),
                            'condition' => $data['condition'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }),
            ]);
    }
}
