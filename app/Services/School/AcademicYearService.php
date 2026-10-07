<?php

namespace App\Services\School;

use App\Enums\AcademicYearStatus;
use App\Enums\PeriodStatus;
use App\Enums\TermStatus;
use App\Models\AcademicYear;
use App\Models\AccountingPeriod;
use App\Models\AcademicTerm;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AcademicYearService
{
    /**
     * Create a tahun ajaran from its canonical name ("2026/2027").
     * The academic year runs 1 July of the first year to 30 June of the second.
     * Terms and the 12 accounting periods are seeded automatically (model hook).
     *
     * @throws InvalidArgumentException when the name is malformed
     */
    public function create(string $name, bool $isDefault = false): AcademicYear
    {
        [$startYear, $endYear] = $this->parseName($name);

        $startsAt = CarbonImmutable::create($startYear, 7, 1);
        $endsAt = CarbonImmutable::create($endYear, 6, 30);

        $default = $isDefault || AcademicYear::query()->doesntExist();

        return DB::transaction(function () use ($name, $startsAt, $endsAt, $default): AcademicYear {
            $year = AcademicYear::query()->create([
                'name' => $name,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $default ? AcademicYearStatus::Active : AcademicYearStatus::Planned,
                'is_default' => false,
            ]);

            if ($default) {
                $this->makeDefault($year);
            }

            return $year;
        });
    }

    /**
     * Seed the 2 semesters and the 12 monthly accounting periods (July–June).
     * Idempotent: skips years that already have terms.
     */
    public function seedTermsAndPeriods(AcademicYear $year): void
    {
        if ($year->terms()->exists()) {
            return;
        }

        DB::transaction(function () use ($year): void {
            $startYear = $year->starts_at->year;

            $year->terms()->createMany([
                [
                    'number' => 1,
                    'name' => 'Semester 1 — Ganjil',
                    'starts_at' => CarbonImmutable::create($startYear, 7, 1),
                    'ends_at' => CarbonImmutable::create($startYear, 12, 31),
                    'status' => TermStatus::Planned,
                ],
                [
                    'number' => 2,
                    'name' => 'Semester 2 — Genap',
                    'starts_at' => CarbonImmutable::create($startYear + 1, 1, 1),
                    'ends_at' => CarbonImmutable::create($startYear + 1, 6, 30),
                    'status' => TermStatus::Planned,
                ],
            ]);

            $july = CarbonImmutable::create($startYear, 7, 1);

            for ($i = 0; $i < 12; $i++) {
                $month = $july->addMonths($i);

                $year->periods()->create([
                    'name' => $month->format('Y-m'),
                    'starts_at' => $month->startOfDay(),
                    'ends_at' => $month->endOfMonth(),
                    'status' => PeriodStatus::Open,
                ]);
            }
        });
    }

    /**
     * Enforce "exactly one default academic year at a time".
     */
    public function makeDefault(AcademicYear $year): void
    {
        DB::transaction(function () use ($year): void {
            AcademicYear::query()
                ->whereKeyNot($year->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $year->forceFill(['is_default' => true]);

            if ($year->status === AcademicYearStatus::Planned) {
                $year->status = AcademicYearStatus::Active;
            }

            $year->save();
        });
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseName(string $name): array
    {
        if (preg_match('/^(\d{4})\/(\d{4})$/', $name, $matches) !== 1) {
            throw new InvalidArgumentException('Format tahun ajaran harus "2026/2027".');
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        if ($endYear !== $startYear + 1) {
            throw new InvalidArgumentException('Tahun akhir harus satu tahun setelah tahun awal.');
        }

        if (AcademicYear::query()->where('name', $name)->exists()) {
            throw new RuntimeException("Tahun ajaran {$name} sudah ada.");
        }

        return [$startYear, $endYear];
    }
}
