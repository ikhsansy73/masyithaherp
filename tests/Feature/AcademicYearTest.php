<?php

namespace Tests\Feature;

use App\Enums\AcademicYearStatus;
use App\Enums\PeriodStatus;
use App\Enums\TermStatus;
use App\Models\AcademicYear;
use App\Models\AcademicTerm;
use App\Models\AccountingPeriod;
use App\Services\School\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_seeds_two_terms_and_twelve_periods(): void
    {
        $year = app(AcademicYearService::class)->create('2026/2027');

        $this->assertSame('2026/2027', $year->name);
        $this->assertTrue($year->is_default);
        $this->assertSame(AcademicYearStatus::Active, $year->status);
        $this->assertSame('2026-07-01', $year->starts_at->toDateString());
        $this->assertSame('2027-06-30', $year->ends_at->toDateString());

        $terms = $year->terms()->orderBy('number')->get();
        $this->assertCount(2, $terms);

        $ganjil = $terms->first();
        $this->assertSame('Semester 1 — Ganjil', $ganjil->name);
        $this->assertSame(1, $ganjil->number);
        $this->assertSame('2026-07-01', $ganjil->starts_at->toDateString());
        $this->assertSame('2026-12-31', $ganjil->ends_at->toDateString());
        $this->assertSame(TermStatus::Planned, $ganjil->status);

        $genap = $terms->last();
        $this->assertSame('Semester 2 — Genap', $genap->name);
        $this->assertSame(2, $genap->number);
        $this->assertSame('2027-01-01', $genap->starts_at->toDateString());
        $this->assertSame('2027-06-30', $genap->ends_at->toDateString());

        $periods = $year->periods()->orderBy('name')->get();
        $this->assertCount(12, $periods);
        $this->assertSame('2026-07', $periods->first()->name);
        $this->assertSame('2027-06', $periods->last()->name);
        $this->assertSame('2026-07-01', $periods->first()->starts_at->toDateString());
        $this->assertSame('2026-07-31', $periods->first()->ends_at->toDateString());
        $periods->each(fn (AccountingPeriod $period) => $this->assertSame(PeriodStatus::Open, $period->status));
    }

    public function test_creating_a_year_directly_on_the_model_also_seeds_terms_and_periods(): void
    {
        $year = AcademicYear::factory()->create(['name' => '2027/2028']);

        $this->assertCount(2, $year->terms);
        $this->assertCount(12, $year->periods);
    }

    public function test_seeding_terms_and_periods_is_idempotent(): void
    {
        $year = app(AcademicYearService::class)->create('2026/2027');

        app(AcademicYearService::class)->seedTermsAndPeriods($year);

        $this->assertSame(2, AcademicTerm::query()->count());
        $this->assertSame(12, AccountingPeriod::query()->count());
    }

    public function test_default_is_exclusive_to_one_year(): void
    {
        $service = app(AcademicYearService::class);
        $first = $service->create('2025/2026');

        $second = $service->create('2026/2027', isDefault: true);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->is_default);
        $this->assertSame(AcademicYearStatus::Active, $second->status);
    }

    public function test_second_year_without_default_flag_stays_planned(): void
    {
        $service = app(AcademicYearService::class);
        $first = $service->create('2025/2026');

        $second = $service->create('2026/2027');

        $this->assertTrue($first->fresh()->is_default);
        $this->assertFalse($second->is_default);
        $this->assertSame(AcademicYearStatus::Planned, $second->status);
    }

    public function test_make_default_switches_default_and_activates_planned_year(): void
    {
        $service = app(AcademicYearService::class);
        $first = $service->create('2025/2026');
        $second = $service->create('2026/2027');

        $service->makeDefault($second);

        $this->assertTrue($second->fresh()->is_default);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertSame(AcademicYearStatus::Active, $second->fresh()->status);
    }

    public function test_rejects_malformed_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format tahun ajaran harus "2026/2027".');

        app(AcademicYearService::class)->create('2026-2027');
    }

    public function test_rejects_end_year_not_adjacent_to_start_year(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tahun akhir harus satu tahun setelah tahun awal.');

        app(AcademicYearService::class)->create('2026/2028');
    }

    public function test_rejects_duplicate_year_name(): void
    {
        app(AcademicYearService::class)->create('2026/2027');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tahun ajaran 2026/2027 sudah ada.');

        app(AcademicYearService::class)->create('2026/2027');
    }
}
