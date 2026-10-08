<?php

namespace App\Filament\Pages;

use App\Enums\StudentAttendanceStatus;
use App\Exceptions\SchoolException;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\School\StudentAttendanceService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    /**
     * Picker state (classroomId, date) held by the page schema.
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * Student id → attendance status value for the grid.
     *
     * @var array<int, string>
     */
    public array $statuses = [];

    public function mount(): void
    {
        $this->form->fill([
            'date' => today()->toDateString(),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('attendance.student.viewAny') ?? false;
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
                    ->options($this->classroomOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadRoster()),
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->maxDate(today())
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadRoster()),
            ])
            ->columns(2)
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
            ->orderBy('academic_year_id')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Classroom $classroom): array => [
                $classroom->getKey() => $classroom->name.' — '.$classroom->academicYear?->name,
            ])
            ->all();
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
     * Attendance day picked in the schema (today when empty).
     */
    public function selectedDate(): string
    {
        $value = $this->data['date'] ?? null;

        return filled($value) ? (string) $value : today()->toDateString();
    }

    /**
     * Load the class roster plus any attendance already saved for the
     * chosen day, so re-opening a day edits instead of resetting.
     */
    public function loadRoster(): void
    {
        $this->statuses = [];

        $classroomId = $this->selectedClassroomId();

        if ($classroomId === null) {
            return;
        }

        $existing = StudentAttendance::query()
            ->where('classroom_id', $classroomId)
            ->where('date', $this->selectedDate())
            ->pluck('status', 'student_id');

        foreach ($this->roster() as $student) {
            $current = $existing[$student->id] ?? StudentAttendanceStatus::Hadir;

            $this->statuses[$student->id] = $current instanceof \BackedEnum ? $current->value : (string) $current;
        }
    }

    /**
     * Set one student's status from the grid buttons.
     */
    public function setStatus(int $studentId, string $status): void
    {
        $valid = array_map(
            fn (StudentAttendanceStatus $case): string => $case->value,
            StudentAttendanceStatus::cases(),
        );

        if (in_array($status, $valid, true)) {
            $this->statuses[$studentId] = $status;
        }
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

    /**
     * Live tally per status for the summary badges.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = [];

        foreach (StudentAttendanceStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }

        foreach ($this->statuses as $status) {
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public function simpan(): void
    {
        $classroomId = $this->selectedClassroomId();

        if ($classroomId === null) {
            Notification::make()->danger()->title('Pilih rombel terlebih dahulu.')->send();

            return;
        }

        if (! auth()->user()?->can('attendance.student.create')) {
            Notification::make()->danger()->title('Anda tidak memiliki izin menyimpan absensi.')->send();

            return;
        }

        $classroom = Classroom::query()->findOrFail($classroomId);

        try {
            $written = app(StudentAttendanceService::class)->saveForClassroom(
                classroom: $classroom,
                date: Carbon::parse($this->selectedDate()),
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
     * @return Collection<int, Student>
     */
    public function roster(): Collection
    {
        $classroomId = $this->selectedClassroomId();

        if ($classroomId === null) {
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
}
