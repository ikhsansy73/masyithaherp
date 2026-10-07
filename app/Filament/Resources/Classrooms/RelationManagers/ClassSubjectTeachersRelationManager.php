<?php

namespace App\Filament\Resources\Classrooms\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassSubjectTeachersRelationManager extends RelationManager
{
    protected static string $relationship = 'classSubjectTeachers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('subject_id')
                ->label('Mata Pelajaran')
                ->relationship('subject', 'name')
                ->required(),
            Select::make('teacher_id')
                ->label('Guru Pengampu')
                ->relationship(
                    'teacher',
                    'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_teaching', true),
                )
                ->required(),
            TextInput::make('jp_per_week')
                ->label('JP / Minggu')
                ->numeric()
                ->integer()
                ->minValue(1),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Mapel')
                    ->sortable(),
                TextColumn::make('teacher.name')
                    ->label('Guru Pengampu')
                    ->sortable(),
                TextColumn::make('jp_per_week')
                    ->label('JP / Minggu'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => false),
                ]),
            ]);
    }
}
