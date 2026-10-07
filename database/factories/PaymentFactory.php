<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\CashAccount;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'KW/'.fake()->unique()->numerify('########'),
            'student_id' => Student::factory(),
            'payment_date' => today(),
            'method' => PaymentMethod::Tunai,
            'cash_account_id' => CashAccount::factory(),
            'amount' => 600_000,
            'reference' => null,
            'received_by' => null,
            'notes' => null,
            'journal_entry_id' => null,
            'reversed_by_payment_id' => null,
        ];
    }
}
