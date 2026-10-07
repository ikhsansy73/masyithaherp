<?php

namespace App\Filament\Resources\Subjects;

use App\Enums\SubjectKelompok;
use App\Filament\Resources\Subjects\Pages\ManageSubjects;
use App\Models\Subject;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Mata Pelajaran';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Mata Pelajaran';
    }

    /**
     * Filament v5 resolves resource-action authorization through the Gate
     * (policies), not the can*() overrides — so the spatie permission
     * matrix is mapped onto Filament abilities here.
     */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'academics.classroom.viewAny',
            'create' => 'academics.classroom.create',
            'update' => 'academics.classroom.update',
            'delete' => 'academics.classroom.delete',
            default => null,
        };

        if ($permission === null) {
            return Response::deny();
        }

        return auth()->user()?->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                TextInput::make('name')
                    ->label('Nama Mapel')
                    ->required()
                    ->maxLength(255),
                Select::make('kelompok')
                    ->label('Kelompok')
                    ->options(SubjectKelompok::class)
                    ->required(),
                TextInput::make('jp_per_week')
                    ->label('JP / Minggu')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(2),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama Mapel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kelompok')
                    ->label('Kelompok')
                    ->badge(),
                TextColumn::make('jp_per_week')
                    ->label('JP / Minggu')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
            'index' => ManageSubjects::route('/'),
        ];
    }
}
