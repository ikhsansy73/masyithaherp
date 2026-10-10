<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\ReportCard;
use App\Models\User;
use App\Settings\SchoolSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Rapor PDF printing (doc 06 §7): per student and per class batch.
 * Staff follow rapor.view row scoping; guardians only see published
 * rapor of the students they guard (portal readiness).
 */
class RaporPdfController extends Controller
{
    public function __construct(
        private readonly SchoolSettings $school,
    ) {}

    public function show(ReportCard $card, Gate $gate)
    {
        $gate->authorize('rapor.view');

        $this->assertCanViewCard(auth()->user(), $card);

        $card->load(['student', 'classroom', 'term.academicYear', 'subjects.subject']);

        return $this->render(collect([$card]), "rapor-{$card->student->nis}.pdf");
    }

    public function batch(Request $request, Classroom $classroom, AcademicTerm $term, Gate $gate)
    {
        $gate->authorize('rapor.view');

        $user = auth()->user();

        if (! $this->canSeeClassroom($user, $classroom)) {
            throw new AuthorizationException;
        }

        $cards = ReportCard::query()
            ->where('classroom_id', $classroom->getKey())
            ->where('academic_term_id', $term->getKey())
            ->whereHas('classroom', fn ($query) => $query->visibleToStaff($user))
            ->with(['student', 'classroom', 'term.academicYear', 'subjects.subject'])
            ->get()
            ->sortBy(fn (ReportCard $card) => $card->student->full_name)
            ->values();

        return $this->render($cards, "rapor-{$classroom->name}.pdf");
    }

    /**
     * Staff see their classrooms' cards; guardians only published cards
     * of the students they guard.
     */
    private function assertCanViewCard(User $user, ReportCard $card): void
    {
        if ($this->canSeeClassroom($user, $card->classroom)) {
            return;
        }

        $isGuardian = $card->isPublished()
            && $card->student->guardians()
                ->where('user_id', $user->getKey())
                ->exists();

        if (! $isGuardian) {
            throw new AuthorizationException;
        }
    }

    /**
     * Doc 09 §4 row scoping: admin roles bypass, staff see owned rombel.
     */
    private function canSeeClassroom(User $user, Classroom $classroom): bool
    {
        if (! $user->can('rapor.view')) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'kepala_sekolah', 'operator_tu', 'bendahara'])) {
            return true;
        }

        $employeeId = $user->employee?->getKey();

        if ($employeeId === null) {
            return false;
        }

        return $classroom->homeroom_teacher_id === $employeeId
            || $classroom->classSubjectTeachers()
                ->where('teacher_id', $employeeId)
                ->exists();
    }

    /**
     * Stream the A4 rapor sheet(s); one page per student for batches.
     *
     * @param  Collection<int, ReportCard>  $cards
     */
    private function render(Collection $cards, string $filename)
    {
        $card = $cards->first();

        $waliKelas = $card === null ? null : $card->classroom->homeroomTeacher;

        return Pdf::loadView('rapor.print', [
            'cards' => $cards,
            'school' => $this->school,
            'waliKelas' => $waliKelas,
            'logoData' => $this->logoDataUri(),
        ])
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    /**
     * School logo as a data URI (dompdf-safe), null when absent.
     */
    private function logoDataUri(): ?string
    {
        $path = public_path('assets/logo.jpg');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($path));
    }
}
