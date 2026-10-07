<?php

namespace App\Filament\Resources\Classrooms\RelationManagers;

use App\Enums\EnrollmentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('student'))
            ->columns([
                TextColumn::make('student.nis')
                    ->label('NIS')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('student.full_name')
                    ->label('Nama Siswa')
                    ->searchable(),
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
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::class),
            ])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => false),
                ]),
            ]);
    }
}
