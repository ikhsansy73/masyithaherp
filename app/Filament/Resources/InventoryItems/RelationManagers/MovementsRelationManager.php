<?php

namespace App\Filament\Resources\InventoryItems\RelationManagers;

use App\Enums\StockMovementType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('movement_date', 'desc')
            ->columns([
                TextColumn::make('movement_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (StockMovementType $state): string => $state === StockMovementType::Masuk ? 'success' : 'warning'),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric(2),
                TextColumn::make('unit_cost')
                    ->label('Harga/Unit')
                    ->money('IDR'),
                TextColumn::make('total_cost')
                    ->label('Total')
                    ->money('IDR'),
                TextColumn::make('purpose')
                    ->label('Peruntukan')
                    ->limit(30)
                    ->placeholder('-'),
                TextColumn::make('journalEntry.number')
                    ->label('Jurnal')
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),
            ]);
    }
}
