<?php

namespace App\Filament\Resources\Kurikulum;

use App\Filament\Resources\Kurikulum\Pages\ManageKurikulum;
use App\Filament\Resources\Kurikulum\Pages\ViewKurikulum;
use App\Filament\Resources\Kurikulum\RelationManagers\ObjectivesRelationManager;
use App\Models\LearningAchievement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

/** CP (Capaian Pembelajaran) with its TP (Tujuan Pembelajaran) children. */
class KurikulumResource extends Resource
{
    protected static ?string $model = LearningAchievement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Kurikulum (CP/TP)';

    protected static ?string $slug = 'kurikulum';

    public static function getModelLabel(): string
    {
        return 'CP';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kurikulum (CP/TP)';
    }

    /**
     * TP (Tujuan Pembelajaran) editor lives in a relation manager on the
     * CP's view page.
     */
    public static function getRelations(): array
    {
        return [
            ObjectivesRelationManager::class,
        ];
    }

    /** spatie permission mapping (doc 09: academics.curriculum). */
    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'academics.curriculum.viewAny',
            'create' => 'academics.curriculum.create',
            'update' => 'academics.curriculum.update',
            'delete' => 'academics.curriculum.delete',
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
                Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                Select::make('fase')
                    ->label('Fase')
                    ->options([
                        'A' => 'Fase A (Kelas 1-2)',
                        'B' => 'Fase B (Kelas 3-4)',
                        'C' => 'Fase C (Kelas 5-6)',
                    ])
                    ->required(),
                TextInput::make('elemen')
                    ->label('Elemen')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Kode CP')
                    ->required()
                    ->maxLength(20),
                Textarea::make('description')
                    ->label('Deskripsi CP')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('subject'))
            ->columns([
                TextColumn::make('subject.name')
                    ->label('Mapel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fase')
                    ->label('Fase')
                    ->badge()
                    ->color('info'),
                TextColumn::make('elemen')
                    ->label('Elemen')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('code')
                    ->label('Kode CP')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi CP')
                    ->limit(60),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('subject')
                    ->label('Mapel')
                    ->relationship('subject', 'name'),
                SelectFilter::make('fase')
                    ->label('Fase')
                    ->options([
                        'A' => 'Fase A (Kelas 1-2)',
                        'B' => 'Fase B (Kelas 3-4)',
                        'C' => 'Fase C (Kelas 5-6)',
                    ]),
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
            'index' => ManageKurikulum::route('/'),
            'view' => ViewKurikulum::route('/{record}'),
        ];
    }
}
