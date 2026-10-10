<?php

namespace App\Filament\Resources\Kurikulum\RelationManagers;

use App\Models\LearningObjective;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** TP (Tujuan Pembelajaran) authored by teachers under each CP. */
class ObjectivesRelationManager extends RelationManager
{
    protected static string $relationship = 'objectives';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode TP')
                    ->required()
                    ->maxLength(30)
                    ->rule(function (?Model $record, RelationManager $livewire): \Closure {
                        $achievementId = $livewire->getOwnerRecord()->getKey();

                        return function (string $attribute, $value, \Closure $fail) use ($achievementId, $record): void {
                            $query = LearningObjective::query()
                                ->where('learning_achievement_id', $achievementId)
                                ->where('code', $value);

                            if ($record !== null) {
                                $query->whereKeyNot($record->getKey());
                            }

                            if ($query->exists()) {
                                $fail("Kode TP '{$value}' sudah dipakai di CP ini.");
                            }
                        };
                    }),
                Textarea::make('description')
                    ->label('Deskripsi TP')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('semester')
                    ->label('Semester')
                    ->options(['1' => 'Semester 1', '2' => 'Semester 2'])
                    ->required(),
                TextInput::make('sequence')
                    ->label('Urutan')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderBy('semester')->orderBy('sequence'))
            ->columns([
                TextColumn::make('code')
                    ->label('Kode TP')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi TP')
                    ->limit(70),
                TextColumn::make('semester')
                    ->label('Smt')
                    ->badge()
                    ->color('info'),
                TextColumn::make('sequence')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
