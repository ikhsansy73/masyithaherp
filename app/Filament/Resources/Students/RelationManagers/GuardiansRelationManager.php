<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\GuardianEducation;
use App\Enums\GuardianRelation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GuardiansRelationManager extends RelationManager
{
    protected static string $relationship = 'guardians';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('relationship')
                ->label('Hubungan')
                ->options(GuardianRelation::class)
                ->required(),
            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),
            TextInput::make('nik')
                ->label('NIK')
                ->numeric()
                ->maxLength(16),
            TextInput::make('occupation')
                ->label('Pekerjaan')
                ->maxLength(255),
            Select::make('education')
                ->label('Pendidikan')
                ->options(GuardianEducation::class),
            TextInput::make('phone')
                ->label('Telepon')
                ->required()
                ->maxLength(20),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            Toggle::make('is_primary_contact')
                ->label('Kontak Utama')
                ->default(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('relationship')
                    ->label('Hubungan')
                    ->badge(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('occupation')
                    ->label('Pekerjaan'),
                TextColumn::make('phone')
                    ->label('Telepon'),
                TextColumn::make('email')
                    ->label('Email'),
                IconColumn::make('is_primary_contact')
                    ->label('Kontak Utama')
                    ->boolean(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
