<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\EnrollmentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

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
                TextColumn::make('classroom.name')
                    ->label('Rombel')
                    ->sortable(),
                TextColumn::make('grade_level')
                    ->label('Tingkat'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (EnrollmentStatus $state): string => match ($state) {
                        EnrollmentStatus::Aktif => 'success',
                        EnrollmentStatus::Lulus => 'info',
                        EnrollmentStatus::Pindah => 'warning',
                        default => 'gray',
                    }),
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
