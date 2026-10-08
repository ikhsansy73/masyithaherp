<?php

namespace App\Console\Commands;

use App\Enums\FeeCategory;
use App\Models\AcademicYear;
use App\Models\FeeType;
use App\Services\Billing\InvoiceBatchService;
use Illuminate\Console\Command;

class BillingGenerateInvoices extends Command
{
    protected $signature = 'billing:generate-invoices
        {--year= : Academic year ID (default: the active year covering the target month)}
        {--month= : Calendar month 1-12 (default: next month)}
        {--grade= : Grade level filter (default: all grades)}';

    protected $description = 'Generate the monthly SPP draft invoice batches for the next month (doc 04 §2)';

    public function handle(InvoiceBatchService $batches): int
    {
        $month = $this->option('month') !== null
            ? (int) $this->option('month')
            : (int) today()->addMonth()->month;

        if ($month < 1 || $month > 12) {
            $this->error('Bulan harus 1-12.');

            return self::FAILURE;
        }

        $year = $this->option('year') !== null
            ? AcademicYear::query()->find((int) $this->option('year'))
            : $this->defaultYear();

        if ($year === null) {
            $this->error('Tahun ajaran aktif tidak ditemukan untuk bulan tersebut.');

            return self::FAILURE;
        }

        $grade = $this->option('grade') !== null ? (int) $this->option('grade') : null;

        // One batch per monthly fee type with structures for this year.
        $feeTypes = FeeType::query()
            ->where('is_active', true)
            ->where('category', FeeCategory::Bulanan->value)
            ->whereHas('structures', fn ($query) => $query->where('academic_year_id', $year->getKey()))
            ->get();

        if ($feeTypes->isEmpty()) {
            $this->warn("Tidak ada struktur biaya bulanan untuk tahun {$year->name}.");

            return self::SUCCESS;
        }

        foreach ($feeTypes as $feeType) {
            $batch = $batches->generate($year, $feeType, $month, $grade);

            if ($batch === null) {
                $this->line("{$feeType->code}: tidak ada siswa eligible.");

                continue;
            }

            $this->info("{$feeType->code}: batch {$batch->id} — {$batch->total_invoices} tagihan, total Rp ".
                number_format($batch->total_amount, 0, ',', '.').", status {$batch->status->label()}.");
        }

        $this->comment('Batch tetap draft — terbitkan dari halaman Batch Tagihan.');

        return self::SUCCESS;
    }

    /**
     * The default academic year (the schedule runs on the 20th for the
     * next month, which always falls inside the current year).
     */
    private function defaultYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('is_default', true)
            ->first();
    }
}
