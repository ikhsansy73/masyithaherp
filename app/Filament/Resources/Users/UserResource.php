<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.user.viewAny') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.user.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('system.user.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('system.user.delete') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Pengguna nonaktif tidak dapat masuk.'),
                CheckboxList::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $role): string => Str::headline($role->name))
                    ->columns(3)
                    ->helperText('Wali kelas biasanya memegang peran guru dan wali kelas sekaligus.'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->color('primary'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
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

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
