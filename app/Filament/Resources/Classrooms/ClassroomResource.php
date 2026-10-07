<?php

namespace App\Filament\Resources\Classrooms;

use App\Filament\Resources\Classrooms\Pages\ManageClassrooms;
use App\Filament\Resources\Classrooms\Pages\ViewClassroom;
use App\Filament\Resources\Classrooms\RelationManagers\ClassSubjectTeachersRelationManager;
use App\Filament\Resources\Classrooms\RelationManagers\EnrollmentsRelationManager;
use App\Models\Classroom;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Rombel';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Rombel';
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
        return $schema->components([
            Select::make('academic_year_id')
                ->label('Tahun Ajaran')
                ->relationship('academicYear', 'name')
                ->required(),
            TextInput::make('name')
                ->label('Nama Rombel (mis. 3A)')
                ->required()
                ->maxLength(10),
            Select::make('grade_level')
                ->label('Tingkat')
                ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6'])
                ->required(),
            Select::make('fase')
                ->label('Fase')
                ->options([
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                ])
                ->required(),
            TextInput::make('capacity')
                ->label('Kapasitas')
                ->numeric()
                ->integer()
                ->minValue(1),
            Select::make('homeroom_teacher_id')
                ->label('Wali Kelas')
                ->relationship('homeroomTeacher', 'name')
                ->searchable()
                ->preload(),
            Select::make('location_id')
                ->label('Ruang')
                ->relationship('location', 'name')
                ->searchable()
                ->preload(),
            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Rombel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('grade_level')
                    ->label('Tingkat')
                    ->sortable(),
                TextColumn::make('fase')
                    ->label('Fase')
                    ->badge(),
                TextColumn::make('homeroomTeacher.name')
                    ->label('Wali Kelas')
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Ruang'),
                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label('Jumlah Siswa')
                    ->counts('enrollments'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name'),
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

    public static function getRelations(): array
    {
        return [
            EnrollmentsRelationManager::class,
            ClassSubjectTeachersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClassrooms::route('/'),
            'view' => ViewClassroom::route('/{record}'),
        ];
    }
}
