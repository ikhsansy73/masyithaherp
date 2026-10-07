<?php

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'item_type' => InvoiceItemType::Posisi,
            'fee_type_id' => null,
            'discount_id' => null,
            'description' => 'SPP',
            'amount' => 600_000,
            'revenue_account_id' => null,
            'fund_id' => null,
        ];
    }

    public function potongan(int $amount, ?int $discountId = null): static
    {
        return $this->state(fn (): array => [
            'item_type' => InvoiceItemType::Potongan,
            'description' => 'Potongan',
            'amount' => $amount,
            'discount_id' => $discountId,
        ]);
    }
}
