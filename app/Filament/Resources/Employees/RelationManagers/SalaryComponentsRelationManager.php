<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\EmployeeSalaryComponent;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SalaryComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'salaryComponents';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('salary_component_id')
                ->label('Komponen')
                ->options(fn (): array => \App\Models\SalaryComponent::query()
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn (\App\Models\SalaryComponent $component): array => [
                        $component->getKey() => $component->code.' - '.$component->name,
                    ])
                    ->all())
                ->searchable()
                ->disabled(fn (?Model $record): bool => $record !== null)
                ->dehydrated(fn (?Model $record): bool => $record === null)
                ->required(),
            TextInput::make('amount')
                ->label('Nominal')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('percent_rate')
                ->label('Persentase')
                ->numeric()
                ->minValue(0)
                ->maxValue(1)
                ->step(0.0001)
                ->nullable(),
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salaryComponent.code')
                    ->label('Kode')
                    ->weight('semibold'),
                TextColumn::make('salaryComponent.name')
                    ->label('Komponen'),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR'),
                TextColumn::make('percent_rate')
                    ->label('%')
                    ->formatStateUsing(fn ($state): string => $state === null ? '-' : number_format((float) $state * 100, 2, ',', '.').'%'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('hr.employee.update') ?? false)
                    ->using(function (Model $record, array $data): Model {
                        $record->update($data);

                        return $record;
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()->can('hr.employee.update')),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('hr.employee.update') ?? false)
                    ->using(function (array $data): EmployeeSalaryComponent {
                        return $this->getOwnerRecord()->salaryComponents()->updateOrCreate(
                            ['salary_component_id' => (int) $data['salary_component_id']],
                            [
                                'amount' => (int) $data['amount'],
                                'percent_rate' => $data['percent_rate'] ?? null,
                                'is_active' => (bool) ($data['is_active'] ?? true),
                            ],
                        );
                    }),
            ]);
    }
}
