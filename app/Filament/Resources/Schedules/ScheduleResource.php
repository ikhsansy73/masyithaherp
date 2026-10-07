<?php

namespace App\Filament\Resources\Schedules;

use App\Enums\ScheduleDay;
use App\Filament\Resources\Schedules\Pages\ManageSchedules;
use App\Models\Schedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Jadwal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jadwal Pelajaran';
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
                Select::make('classroom_id')
                    ->label('Rombel')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('subject_id')
                    ->label('Mapel')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('teacher_id')
                    ->label('Guru Pengampu')
                    ->relationship(
                        'teacher',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_teaching', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('academic_term_id')
                    ->label('Semester')
                    ->relationship('academicTerm', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('day')
                    ->label('Hari')
                    ->options(ScheduleDay::class)
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->label('Jam Selesai')
                    ->seconds(false)
                    ->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classroom.name')
                    ->label('Rombel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subject.name')
                    ->label('Mapel')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('teacher.name')
                    ->label('Guru')
                    ->searchable(),
                TextColumn::make('academicTerm.name')
                    ->label('Semester')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('day')
                    ->label('Hari')
                    ->badge()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Mulai')
                    ->time()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Selesai')
                    ->time(),
            ])
            ->filters([
                SelectFilter::make('classroom_id')
                    ->label('Rombel')
                    ->relationship('classroom', 'name'),
                SelectFilter::make('day')
                    ->label('Hari')
                    ->options(ScheduleDay::class),
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
            'index' => ManageSchedules::route('/'),
        ];
    }
}
