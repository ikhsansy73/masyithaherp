<?php

namespace App\Console\Commands;

use App\Exceptions\AccountingException;
use App\Models\AccountingPeriod;
use App\Models\User;
use App\Services\Assets\DepreciationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Doc 07 §2: the monthly depreciation run. `--period` takes 'Y-m';
 * defaults to the previous calendar month so a cron on the 1st processes
 * the month that just ended.
 */
class AssetsDepreciate extends Command
{
    protected $signature = 'assets:depreciate
        {--period= : Accounting period Y-m (default: previous month)}';

    protected $description = 'Jalankan penyusutan aset bulanan (JE #13)';

    public function handle(DepreciationService $depreciation): int
    {
        $name = $this->option('period') !== null
            ? $this->option('period')
            : today()->subMonth()->format('Y-m');

        $period = AccountingPeriod::query()->where('name', $name)->first();

        if ($period === null) {
            $this->error("Periode akuntansi {$name} tidak ditemukan.");

            return self::FAILURE;
        }

        try {
            $result = $depreciation->run($period, userId: $this->systemUserId());
        } catch (AccountingException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result->count === 0) {
            $this->info("Tidak ada aset yang perlu disusutkan untuk {$name}.");

            return self::SUCCESS;
        }

        $this->info("Penyusutan {$name}: {$result->count} aset disusutkan, jurnal {$result->entry->number} dibukukan.");

        return self::SUCCESS;
    }

    /**
     * Scheduled runs have no signed-in user, but journal_entries.created_by
     * is a required FK — journal the run under a dedicated system user.
     */
    private function systemUserId(): int
    {
        return (int) User::query()->firstOrCreate(
            ['email' => 'system@masyithah.sch.id'],
            ['name' => 'Sistem', 'password' => Hash::make(Str::password(32))],
        )->getKey();
    }
}
