<?php

namespace Database\Factories;

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Models\AcademicYear;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 *
 * Journal entries are normally created exclusively by
 * JournalPostingService — the only write path. This factory exists for
 * edge cases (e.g. seeding deliberately unbalanced rows for the
 * accounting:verify-balance command). The accounting period is derived
 * from an academic year so the entry date always lands inside an
 * auto-seeded period.
 */
class JournalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => sprintf('JE/2026-08/%06d', $this->faker->unique()->numberBetween(1, 999999)),
            'entry_date' => '2026-08-15',
            'accounting_period_id' => function (array $attributes) {
                $year = AcademicYear::factory()->forStartYear(2026)->create();

                return $year->periods()->where('name', substr((string) $attributes['entry_date'], 0, 7))->value('id');
            },
            'description' => 'Jurnal '.$this->faker->words(3, true),
            'source' => JournalSource::Otomatis,
            'status' => JournalStatus::Posted,
            'created_by' => User::factory(),
        ];
    }

    public function void(): static
    {
        return $this->state(fn (): array => [
            'status' => JournalStatus::Void,
            'voided_at' => now(),
            'voided_reason' => 'Uji pembatalan',
        ]);
    }
}
