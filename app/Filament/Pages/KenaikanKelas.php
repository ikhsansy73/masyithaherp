<?php

namespace App\Filament\Pages;

use App\Exceptions\SchoolException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\School\StudentMovementService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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

    public ?int $classroomId = null;

    public ?int $targetYearId = null;

    public string $movementDate;

    public ?string $notes = null;

    /** @var array<int, string> */
    public array $decisions = [];

    public function mount(): void
    {
        $this->movementDate = today()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('students.student.viewAny') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<int, string>
     */
    public function classroomOptions(): array
    {
        return Classroom::query()
            ->with('academicYear')
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Classroom $c): int => $c->academic_year_id)
            ->mapWithKeys(fn (Classroom $c): array => [
                $c->getKey() => $c->name.' — '.$c->academicYear?->name,
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
        if ($this->classroomId === null) {
            return [];
        }

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

    public function updatedClassroomId(): void
    {
        $this->targetYearId = null;
        $this->decisions = [];
        $this->loadRoster();
    }

    public function updatedTargetYearId(): void
    {
        $this->decisions = [];
        $this->loadRoster();
    }

    public function semuaNaik(): void
    {
        $this->decisions = array_fill_keys(array_keys($this->decisions), 'naik');
    }

    public function loadRoster(): void
    {
        $this->decisions = [];

        foreach ($this->roster() as $student) {
            $this->decisions[$student->id] = 'naik';
        }
    }

    /**
     * @return Collection<int, Student>
     */
    public function roster(): Collection
    {
        if ($this->classroomId === null || $this->targetYearId === null) {
            return collect();
        }

        $source = $this->sourceYear();

        if ($source === null) {
            return collect();
        }

        return Student::query()
            ->select(['students.id', 'students.nis', 'students.full_name'])
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('student_enrollments.classroom_id', $this->classroomId)
            ->where('student_enrollments.academic_year_id', $source->getKey())
            ->where('student_enrollments.status', 'aktif')
            ->orderBy('students.full_name')
            ->get();
    }

    /**
     * Source rombel's academic year (null when nothing is picked yet).
     */
    public function sourceYear(): ?AcademicYear
    {
        if ($this->classroomId === null) {
            return null;
        }

        $classroom = Classroom::query()->with('academicYear')->find($this->classroomId);

        return $classroom?->academicYear;
    }

    public function simpan(): void
    {
        if (! auth()->user()?->can('students.student.update')) {
            Notification::make()->danger()->title('Anda tidak memiliki izin melakukan kenaikan kelas.')->send();

            return;
        }

        if ($this->classroomId === null || $this->targetYearId === null) {
            Notification::make()->danger()->title('Pilih rombel dan tahun ajaran target terlebih dahulu.')->send();

            return;
        }

        if ($this->decisions === []) {
            Notification::make()->danger()->title('Tidak ada siswa untuk diproses.')->send();

            return;
        }

        try {
            $moved = app(StudentMovementService::class)->promoteClassroom(
                from: Classroom::query()->findOrFail($this->classroomId),
                targetYear: AcademicYear::query()->findOrFail($this->targetYearId),
                decisions: $this->decisions,
                actor: auth()->user(),
                movementDate: Carbon::parse($this->movementDate),
                notes: $this->notes,
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
}
