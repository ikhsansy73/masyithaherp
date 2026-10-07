<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\PpdbStatus;
use App\Enums\Religion;
use App\Models\AcademicYear;
use App\Models\PpdbRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PpdbRegistration>
 */
class PpdbRegistrationFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        self::$sequence++;

        return [
            'registration_no' => 'PPDB/'.today()->year.'/'.str_pad((string) self::$sequence, 6, '0', STR_PAD_LEFT),
            'academic_year_id' => AcademicYear::factory(),
            'applicant_name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-7 years', '-5 years'),
            'religion' => Religion::Islam,
            'nik' => fake()->unique()->numerify('################'),
            'origin_tk' => 'TK Aisyiyah',
            'address' => fake()->address(),
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->name('female'),
            'parent_phone' => fake()->e164PhoneNumber(),
            'status' => PpdbStatus::Baru,
            'registered_at' => now(),
        ];
    }

    public function forYear(AcademicYear $year): static
    {
        return $this->state(fn (): array => ['academic_year_id' => $year->id]);
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'status' => PpdbStatus::Verifikasi,
            'verified_by' => User::factory(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => ['status' => PpdbStatus::Diterima]);
    }
}
