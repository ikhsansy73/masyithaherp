<?php

namespace App\Filament\Pages;

use App\Enums\StudentAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Services\School\StudentAttendanceService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Attendance input grid (doc 06 §4): pick a rombel + date, mark every
 * student H/S/I/A, save the whole class at once via the service.
 */
class AbsensiSiswa extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Absensi Siswa';

    protected static ?string $title = 'Absensi Siswa';

    protected string $view = 'filament.pages.absensi-siswa';

    public ?int $classroomId = null;

    public string $date;

    /**
     * Student id → attendance status value for the grid.
     *
     * @var array<int, string>
     */
    public array $statuses = [];

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('attendance.student.viewAny') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Active rombel options for the picker.
     *
     * @return array<int, string>
     */
    public function classroomOptions(): array
    {
        return Classroom::query()
            ->where('is_active', true)
            ->orderBy('academic_year_id')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->getKey() => $classroom->name.' — '.$classroom->academicYear?->name,
            ])
            ->all();
    }

    public function updatedClassroomId(): void
    {
        $this->loadRoster();
    }

    /**
     * Load the class roster plus any attendance already saved for the
     * chosen day, so re-opening a day edits instead of resetting.
     */
    public function loadRoster(): void
    {
        $this->statuses = [];

        if ($this->classroomId === null) {
            return;
        }

        $students = $this->roster();

        $existing = StudentAttendance::query()
            ->where('classroom_id', $this->classroomId)
            ->where('date', $this->date)
            ->pluck('status', 'student_id');

        foreach ($students as $student) {
            $this->statuses[$student->id] = $existing[$student->id] ?? StudentAttendanceStatus::Hadir->value;
        }
    }

    public function updatedDate(): void
    {
        $this->loadRoster();
    }

    /**
     * Set every student to Hadir (the common case).
     */
    public function semuaHadir(): void
    {
        foreach (array_keys($this->statuses) as $studentId) {
            $this->statuses[$studentId] = StudentAttendanceStatus::Hadir->value;
        }
    }

    public function simpan(): void
    {
        if ($this->classroomId === null) {
            Notification::make()->danger()->title('Pilih rombel terlebih dahulu.')->send();

            return;
        }

        if (! auth()->user()?->can('attendance.student.create')) {
            Notification::make()->danger()->title('Anda tidak memiliki izin menyimpan absensi.')->send();

            return;
        }

        $classroom = Classroom::query()->findOrFail($this->classroomId);

        try {
            $written = app(StudentAttendanceService::class)->saveForClassroom(
                classroom: $classroom,
                date: Carbon::parse($this->date),
                statuses: $this->statuses,
                recordedBy: auth()->user(),
            );

            Notification::make()
                ->success()
                ->title('Absensi tersimpan')
                ->body($written.' siswa tercatat untuk '.$classroom->name.'.')
                ->send();
        } catch (SchoolException $exception) {
            Notification::make()
                ->danger()
                ->title('Gagal menyimpan absensi')
                ->body($exception->getMessage())
                ->send();
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Student>
     */
    public function roster(): \Illuminate\Support\Collection
    {
        return \App\Models\Student::query()
            ->select(['students.id', 'students.nis', 'students.full_name'])
            ->join('student_enrollments', 'student_enrollments.student_id', '=', 'students.id')
            ->where('student_enrollments.classroom_id', $this->classroomId)
            ->where('student_enrollments.status', 'aktif')
            ->orderBy('students.full_name')
            ->get();
    }
}
