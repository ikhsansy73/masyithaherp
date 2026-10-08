<?php

namespace App\Filament\Pages;

use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\School\StudentMovementService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Promotion wizard (doc 06 §3): pick a source rombel + the target
 * academic year, decide per student, and run the whole class through
 * StudentMovementService::promoteClassroom.
 */
class KenaikanKelas extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static ?string $title = 'Kenaikan Kelas';

    protected string $view = 'filament.pages.kenaikan-kelas';

    /**
     * Picker state (classroomId, targetYearId, movementDate, notes)
     * held by the page schema.
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Student id → movement decision for the grid.
     *
     * @var array<int, string>
     */
    public array $decisions = [];

    public function mount(): void
    {
        $this->form->fill([
            'movementDate' => today()->toDateString(),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('students.student.viewAny') ?? false;
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
                    ->label('Rombel Sumber')
                    ->options($this->classroomOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->data['targetYearId'] = null;
                        $this->loadRoster();
                    }),
                Select::make('targetYearId')
                    ->label('Tahun Ajaran Target')
                    ->options(fn (): array => $this->targetYearOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadRoster()),
                DatePicker::make('movementDate')
                    ->label('Tanggal Mutasi')
                    ->maxDate(today())
                    ->live(),
                Textarea::make('notes')
                    ->label('Catatan (opsional)')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    /**
     * Active rombel options for the picker.
     *
     * @return array<int, string>
     */
    public function classroomOptions(): array
    {
        return Classroom::query()
            ->with('academicYear')
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Classroom $classroom): int => $classroom->academic_year_id)
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->getKey() => $classroom->name.' — '.$classroom->academicYear?->name,
            ])
            ->all();
    }

    /**
     * Only years starting after the source year ends are legal targets
     * (mirrors the service guard).
     *
     * @return array<int, string>
     */
    public function targetYearOptions(): array
    {
        $source = $this->sourceYear();

        if ($source === null) {
            return [];
        }

        return AcademicYear::query()
            ->where('starts_at', '>', $source->ends_at)
            ->orderBy('starts_at')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Source rombel id currently picked in the schema (null when none).
     */
    public function selectedClassroomId(): ?int
    {
        $value = $this->data['classroomId'] ?? null;

        return filled($value) ? (int) $value : null;
    }

    /**
     * Target academic year id currently picked in the schema.
     */
    public function selectedTargetYearId(): ?int
    {
        $value = $this->data['targetYearId'] ?? null;

        return filled($value) ? (int) $value : null;
    }

    /**
     * Movement date picked in the schema (today when empty).
     */
    public function selectedMovementDate(): string
    {
        $value = $this->data['movementDate'] ?? null;

        return filled($value) ? (string) $value : today()->toDateString();
    }

    /**
     * Source rombel's academic year (null when nothing is picked yet).
     */
    public function sourceYear(): ?AcademicYear
    {
        $classroomId = $this->selectedClassroomId();

        if ($classroomId === null) {
            return null;
        }

        $classroom = Classroom::query()->with('academicYear')->find($classroomId);

        return $classroom?->academicYear;
    }

    public function loadRoster(): void
    {
        $this->decisions = [];

        foreach ($this->roster() as $student) {
            $this->decisions[$student->id] = 'naik';
        }
    }

    /**
     * Set one student's decision from the grid buttons.
     */
    public function setDecision(int $studentId, string $decision): void
    {
        if (in_array($decision, StudentMovementService::DECISIONS, true)) {
            $this->decisions[$studentId] = $decision;
        }
    }

    /**
     * Set every student to naik (the common case).
     */
    public function semuaNaik(): void
    {
        $this->decisions = array_fill_keys(array_keys($this->decisions), 'naik');
    }

    /**
     * How many students are set to naik out of the whole roster.
     */
    public function naikCount(): int
    {
        return count(array_filter($this->decisions, fn (string $decision): bool => $decision === 'naik'));
    }

    /**
     * Short button label for a decision value.
     */
    public function decisionLabel(string $decision): string
    {
        return match ($decision) {
            'naik' => 'Naik',
            'tinggal_kelas' => 'Tinggal',
            'lulus' => 'Lulus',
            'mutasi_keluar' => 'Mutasi',
            'keluar' => 'Keluar',
            default => $decision,
        };
    }

    /**
     * Filament color for a decision button when selected.
     */
    public function decisionColor(string $decision): string
    {
        return match ($decision) {
            'naik' => 'success',
            'tinggal_kelas' => 'warning',
            'lulus' => 'primary',
            'mutasi_keluar' => 'info',
            'keluar' => 'gray',
            default => 'gray',
        };
    }

    public function simpan(): void
    {
        if (! auth()->user()?->can('students.student.update')) {
            Notification::make()->danger()->title('Anda tidak memiliki izin melakukan kenaikan kelas.')->send();

            return;
        }

        $classroomId = $this->selectedClassroomId();
        $targetYearId = $this->selectedTargetYearId();

        if ($classroomId === null || $targetYearId === null) {
            Notification::make()->danger()->title('Pilih rombel sumber dan tahun ajaran target terlebih dahulu.')->send();

            return;
        }

        if ($this->decisions === []) {
            Notification::make()->danger()->title('Tidak ada siswa untuk diproses.')->send();

            return;
        }

        try {
            $moved = app(StudentMovementService::class)->promoteClassroom(
                from: Classroom::query()->findOrFail($classroomId),
                targetYear: AcademicYear::query()->findOrFail($targetYearId),
                decisions: $this->decisions,
                actor: auth()->user(),
                movementDate: Carbon::parse($this->selectedMovementDate()),
                notes: filled($this->data['notes'] ?? null) ? (string) $this->data['notes'] : null,
            );

            Notification::make()
                ->success()
                ->title('Kenaikan kelas berhasil')
                ->body($moved.' siswa diproses.')
                ->send();

            $this->loadRoster();
        } catch (SchoolException $exception) {
            Notification::make()
                ->danger()
                ->title('Kenaikan kelas gagal')
                ->body($exception->getMessage())
                ->send();
        }
    }

    /**
     * @return Collection<int, Student>
     */
    public function roster(): Collection
    {
        $classroomId = $this->selectedClassroomId();
        $source = $this->sourceYear();

        if ($classroomId === null || $source === null) {
            return collect();
        }

        return Student::query()
            ->select(['students.id', 'students.nis', 'students.full_name'])
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('student_enrollments.classroom_id', $classroomId)
            ->where('student_enrollments.academic_year_id', $source->getKey())
            ->where('student_enrollments.status', 'aktif')
            ->orderBy('students.full_name')
            ->get();
    }
}
