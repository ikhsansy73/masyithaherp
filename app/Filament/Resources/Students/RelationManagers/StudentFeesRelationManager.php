<?php

namespace App\Filament\Resources\Students\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentFeesRelationManager extends RelationManager
{
    protected static string $relationship = 'fees';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('feeType.name')
                    ->label('Jenis Iuran')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('months')
                    ->label('Bulan'),
                TextColumn::make('first_month')
                    ->label('Bulan Mulai'),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif'),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => false),
                ]),
            ]);
    }
}
