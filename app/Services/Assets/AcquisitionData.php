<?php

namespace App\Services\Assets;

use App\Enums\AssetCondition;
use App\Enums\FundingSource;
use Carbon\CarbonInterface;

/**
 * Payload for AssetService::acquire() — the acquisition form's required
 * userId sits before the optionals so the trailing optionals keep working
 * as true defaults (doc 07 §1). paymentMode selects the JE #12 credit
 * beli-kredit: 'kas_bank' posts the credit to the kas/bank account;
 * 'utang' posts Cr 2-1400 Utang Vendor (rule #12, credit purchase).
 */
final class AcquisitionData
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $name,
        public readonly CarbonInterface $acquisitionDate,
        public readonly int $cost,
        public readonly int $fundId,
        public readonly FundingSource $fundingSource,
        public readonly AssetCondition $condition,
        public readonly int $locationId,
        public readonly int $userId,
        public readonly ?int $custodianId = null,
        public readonly ?string $brandModel = null,
        public readonly ?string $serialNo = null,
        public readonly ?int $usefulLifeMonths = null,
        public readonly ?int $salvageValue = null,
        public readonly string $paymentMode = 'kas_bank',
        public readonly ?int $cashAccountId = null,
        public readonly ?string $notes = null,
    ) {}
}
