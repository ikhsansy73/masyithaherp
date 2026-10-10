<?php

namespace App\Filament\Resources\JurnalMengajar;

use App\Filament\Resources\JurnalMengajar\Pages\ManageJurnalMengajar;
use App\Models\Classroom;
use App\Models\ClassSubjectTeacher;
use App\Models\Employee;
use App\Models\LearningObjective;
use App\Models\TeacherLearningJournal;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Jurnal mengajar harian (doc 06 §5). */
class JurnalMengajarResource extends Resource
{
    protected static ?string $model = TeacherLearningJournal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Jurnal Mengajar';

    protected static ?string $slug = 'jurnal-mengajar';

    public static function getModelLabel(): string
    {
        return 'Jurnal Mengajar';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->visibleToStaff(auth()->user())
                ->with(['classroom.academicYear', 'subject', 'teacher', 'objective']))
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('classroom.name')
                    ->label('Rombel')
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Mapel'),
                TextColumn::make('teacher.name')
                    ->label('Guru'),
                TextColumn::make('topic')
                    ->label('Materi')
                    ->searchable(),
                TextColumn::make('objective.code')
                    ->label('TP')
                    ->placeholder('-'),
                TextColumn::make('method')
                    ->label('Metode')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('classroom')
                    ->label('Rombel')
                    ->relationship('classroom', 'name'),
                SelectFilter::make('subject')
                    ->label('Mapel')
                    ->relationship('subject', 'name'),
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
            'index' => ManageJurnalMengajar::route('/'),
        ];
    }

    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        $ability = $action instanceof \UnitEnum ? $action->name : $action;

        $permission = match ($ability) {
            'viewAny', 'view' => 'academics.journal.viewAny',
            'create' => 'academics.journal.create',
            'update' => 'academics.journal.update',
            'delete' => 'academics.journal.delete',
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
                    ->options(fn (): array => static::classroomOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->options(fn (Get $get): array => static::subjectOptionsFor($get('classroom_id')))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('teacher_id')
                    ->label('Guru')
                    ->options(fn (): array => Employee::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->default(fn (): ?int => auth()->user()?->employee?->getKey())
                    ->required(),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->maxDate(today())
                    ->default(today())
                    ->required(),
                Select::make('learning_objective_id')
                    ->label('TP yang Dibahas')
                    ->options(fn (Get $get): array => static::objectiveOptionsFor($get('classroom_id'), $get('subject_id')))
                    ->searchable()
                    ->preload(),
                TextInput::make('topic')
                    ->label('Materi')
                    ->required()
                    ->maxLength(255),
                TextInput::make('method')
                    ->label('Metode')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Active rombel visible to the current staff user.
     *
     * @return array<int, string>
     */
    public static function classroomOptions(): array
    {
        $user = auth()->user();

        return Classroom::query()
            ->with('academicYear')
            ->where('is_active', true)
            ->visibleToStaff($user)
            ->orderBy('academic_year_id')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->getKey() => $classroom->name.' — '.$classroom->academicYear?->name,
            ])
            ->all();
    }

    /**
     * Subjects taught in the chosen rombel (class_subject_teachers).
     *
     * @return array<int, string>
     */
    public static function subjectOptionsFor(int|string|null $classroomId): array
    {
        if (! filled($classroomId)) {
            return [];
        }

        return ClassSubjectTeacher::query()
            ->where('classroom_id', (int) $classroomId)
            ->with('subject')
            ->get()
            ->mapWithKeys(fn (ClassSubjectTeacher $cst): array => [
                $cst->subject_id => $cst->subject?->name ?? (string) $cst->subject_id,
            ])
            ->all();
    }

    /**
     * TPs of the chosen subject and the rombel's fase.
     *
     * @return array<int, string>
     */
    public static function objectiveOptionsFor(int|string|null $classroomId, int|string|null $subjectId): array
    {
        if (! filled($classroomId) || ! filled($subjectId)) {
            return [];
        }

        $classroom = Classroom::query()->find((int) $classroomId);

        if ($classroom === null) {
            return [];
        }

        return LearningObjective::query()
            ->whereHas('achievement', fn ($query) => $query
                ->where('subject_id', (int) $subjectId)
                ->where('fase', $classroom->fase))
            ->orderBy('semester')
            ->orderBy('sequence')
            ->get()
            ->mapWithKeys(fn (LearningObjective $objective): array => [
                $objective->getKey() => $objective->code.' — '.$objective->description,
            ])
            ->all();
    }
}
