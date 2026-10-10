<?php

namespace App\Filament\Pages;

use App\Enums\LearningPredicate;
use App\Exceptions\SchoolException;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\Academics\AssessmentService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Score input grid (doc 06 §6): pick rombel + semester + penilaian, fill
 * every student's numeric score or rubric predicate, save via the service.
 */
class InputNilai extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Input Nilai';

    protected static ?string $title = 'Input Nilai';

    protected string $view = 'filament.pages.input-nilai';

    /**
     * Picker state (classroomId, academicTermId, assessmentId).
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Student id → ['score' => ?string, 'predicate' => ?string].
     *
     * @var array<int, array{score: ?string, predicate: ?string}>
     */
    public array $scores = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('academics.assessment.viewAny') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('classroomId')
                    ->label('Rombel')
                    ->options(fn (): array => $this->classroomOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetGrid()),
                Select::make('academicTermId')
                    ->label('Semester')
                    ->options(fn (): array => static::termOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetGrid()),
                Select::make('assessmentId')
                    ->label('Penilaian')
                    ->options(fn (Get $get): array => static::assessmentOptionsFor(
                        (int) $get('classroomId'),
                        (int) $get('academicTermId'),
                    ))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadScores()),
            ])
            ->columns(3)
            ->statePath('data');
    }

    /**
     * Active rombel visible to the current staff user.
     *
     * @return array<int, string>
     */
    public function classroomOptions(): array
    {
        return Classroom::query()
            ->with('academicYear')
            ->where('is_active', true)
            ->visibleToStaff(auth()->user())
            ->orderBy('academic_year_id')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->getKey() => $classroom->name.' — '.$classroom->academicYear?->name,
            ])
            ->all();
    }

    /**
     * Academic terms, newest year first.
     *
     * @return array<int, string>
     */
    public static function termOptions(): array
    {
        return AcademicTerm::query()
            ->with('academicYear')
            ->orderByDesc('academic_year_id')
            ->get()
            ->mapWithKeys(fn (AcademicTerm $term): array => [
                $term->getKey() => $term->name.' — '.$term->academicYear?->name,
            ])
            ->all();
    }

    /**
     * Assessments for the picked rombel × term, scoped to the user.
     *
     * @return array<int, string>
     */
    public static function assessmentOptionsFor(int $classroomId, int $termId): array
    {
        if ($classroomId === 0 || $termId === 0) {
            return [];
        }

        return static::activeAssessmentsQuery($classroomId, $termId)
            ->get()
            ->mapWithKeys(fn (Assessment $assessment): array => [
                $assessment->getKey() => $assessment->subject?->name.' — '.$assessment->name
                    .' ('.$assessment->type->getLabel().' · '.$assessment->dimension->getLabel().')',
            ])
            ->all();
    }

    /**
     * The picked assessment, re-checked against the user's scope.
     */
    public function activeAssessment(): ?Assessment
    {
        $value = $this->data['assessmentId'] ?? null;

        if (! filled($value)) {
            return null;
        }

        return static::activeAssessmentsQuery(
            (int) ($this->data['classroomId'] ?? 0),
            (int) ($this->data['academicTermId'] ?? 0),
        )
            ->whereKey((int) $value)
            ->first();
    }

    /**
     * Scoped assessments query for the picked rombel × term.
     */
    protected static function activeAssessmentsQuery(int $classroomId, int $termId): Builder
    {
        return Assessment::query()
            ->where('classroom_id', $classroomId)
            ->where('academic_term_id', $termId)
            ->visibleToStaff(auth()->user())
            ->with(['subject', 'classroom']);
    }

    /**
     * Rombel id currently picked in the schema (null when none).
     */
    public function selectedClassroomId(): ?int
    {
        $value = $this->data['classroomId'] ?? null;

        return filled($value) ? (int) $value : null;
    }

    /**
     * Term id currently picked in the schema (null when none).
     */
    public function selectedTermId(): ?int
    {
        $value = $this->data['academicTermId'] ?? null;

        return filled($value) ? (int) $value : null;
    }

    /**
     * Clear the grid when the picker changes.
     */
    public function resetGrid(): void
    {
        $this->scores = [];
    }

    /**
     * Load the roster plus any scores already saved for the picked
     * assessment, so re-opening edits instead of resetting.
     */
    public function loadScores(): void
    {
        $this->scores = [];

        $assessment = $this->activeAssessment();

        if ($assessment === null) {
            return;
        }

        $existing = $assessment->scores()
            ->pluck('score', 'student_id');
        $predicates = $assessment->scores()
            ->pluck('predicate', 'student_id');

        foreach ($this->roster() as $student) {
            $score = $existing[$student->id] ?? null;
            $predicate = $predicates[$student->id] ?? null;

            $this->scores[$student->id] = [
                'score' => $score !== null ? (string) $score : null,
                'predicate' => $predicate instanceof \BackedEnum ? $predicate->value : $predicate,
            ];
        }
    }

    /**
     * Valid rubric predicates for the grid selects.
     *
     * @return array<string, string>
     */
    public static function predicateOptions(): array
    {
        return collect(LearningPredicate::cases())
            ->mapWithKeys(fn (LearningPredicate $case): array => [$case->value => $case->getLabel()])
            ->all();
    }

    /**
     * Students actively enrolled in the picked rombel.
     *
     * @return Collection<int, Student>
     */
    public function roster(): Collection
    {
        $classroomId = (int) ($this->data['classroomId'] ?? 0);

        if ($classroomId === 0) {
            return collect();
        }

        return Student::query()
            ->select(['students.id', 'students.nis', 'students.full_name'])
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('student_enrollments.classroom_id', $classroomId)
            ->where('student_enrollments.status', 'aktif')
            ->orderBy('students.full_name')
            ->get();
    }

    /**
     * Save the whole grid through the service.
     */
    public function simpan(): void
    {
        $assessment = $this->activeAssessment();

        if ($assessment === null) {
            Notification::make()->danger()->title('Pilih penilaian terlebih dahulu.')->send();

            return;
        }

        if (! auth()->user()?->can('academics.assessment.create')) {
            Notification::make()->danger()->title('Anda tidak memiliki izin menyimpan nilai.')->send();

            return;
        }

        try {
            $written = app(AssessmentService::class)->saveScores(
                assessment: $assessment,
                scores: $this->scores,
                recorder: auth()->user(),
            );

            Notification::make()
                ->success()
                ->title('Nilai tersimpan')
                ->body($written.' siswa tercatat untuk '.$assessment->name.'.')
                ->send();

            $this->loadScores();
        } catch (SchoolException $exception) {
            Notification::make()
                ->danger()
                ->title('Gagal menyimpan nilai')
                ->body($exception->getMessage())
                ->send();
        }
    }
}
