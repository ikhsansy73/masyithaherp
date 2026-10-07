<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\User;
use App\Services\Accounting\JournalDraft;
use App\Services\Accounting\JournalDraftLine;
use App\Services\Accounting\JournalPostingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VerifyBalanceCommandTest extends TestCase
{
    use RefreshDatabase;

    private JournalPostingService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            \Database\Seeders\FundSeeder::class,
            \Database\Seeders\AccountSeeder::class,
        ]);

        AcademicYear::factory()->forStartYear(2026)->create();

        $this->service = app(JournalPostingService::class);
        $this->user = User::factory()->create();
    }

    public function test_balanced_ledger_succeeds_without_notification(): void
    {
        $this->service->post($this->draft());

        $this->artisan('accounting:verify-balance')
            ->expectsOutputToContain('Seluruh jurnal seimbang.')
            ->assertSuccessful();

        $this->assertDatabaseEmpty('notifications');
    }

    public function test_empty_ledger_succeeds_without_notification(): void
    {
        $this->artisan('accounting:verify-balance')->assertSuccessful();

        $this->assertDatabaseEmpty('notifications');
    }

    public function test_unbalanced_entry_fails_and_notifies_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $entry = $this->service->post($this->draft());

        // Corrupt the ledger behind the service's back: the per-line CHECK
        // constraint allows a one-sided line, so this extra rupiah makes the
        // entry unbalanced (SUM(debit) <> SUM(credit)).
        $entry->lines()->create([
            'account_id' => Account::query()->where('code', '1-1100')->value('id'),
            'fund_id' => null,
            'debit' => 1,
            'credit' => 0,
            'memo' => 'Korupsi uji',
        ]);

        $this->artisan('accounting:verify-balance')
            ->expectsOutputToContain($entry->number)
            ->assertFailed();

        $this->assertDatabaseHas('notifications', [
            'type' => DatabaseNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);

        $notification = \Illuminate\Notifications\DatabaseNotification::query()->sole();
        $this->assertStringContainsString('jurnal tidak seimbang', (string) $notification->data['title']);
    }

    public function test_voided_pair_nets_to_zero_and_passes(): void
    {
        $entry = $this->service->post($this->draft());
        $this->service->void($entry, 'Salah input');

        $this->artisan('accounting:verify-balance')->assertSuccessful();

        $this->assertDatabaseEmpty('notifications');
    }

    private function draft(): JournalDraft
    {
        $cash = Account::query()->where('code', '1-1100')->firstOrFail();
        $revenue = Account::query()->where('code', '4-1900')->firstOrFail();

        return new JournalDraft(
            entryDate: Carbon::parse('2026-10-05'),
            description: 'Jurnal uji verify-balance',
            userId: $this->user->id,
            lines: [
                JournalDraftLine::debit($cash->id, 100_000),
                JournalDraftLine::credit($revenue->id, 100_000),
            ],
        );
    }
}
