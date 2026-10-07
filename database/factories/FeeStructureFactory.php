<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Fund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeStructure>
 */
class FeeStructureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'fee_type_id' => FeeType::factory(),
            'grade_level' => null,

            'fund_id' => Fund::factory(),
            'amount' => 600_000,
        ];
    }
}
