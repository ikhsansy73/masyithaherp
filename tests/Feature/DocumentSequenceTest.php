<?php

namespace Tests\Feature;

use App\Models\DocumentSequence;
use App\Services\Shared\DocumentSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_call_returns_number_one_with_prefix(): void
    {
        $service = app(DocumentSequenceService::class);

        $this->assertSame('KW/2026/000001', $service->next('kwitansi', '2026-07', 'KW/2026/'));
    }

    public function test_subsequent_calls_increment(): void
    {
        $service = app(DocumentSequenceService::class);

        $this->assertSame('KW/2026/000001', $service->next('kwitansi', '2026-07', 'KW/2026/'));
        $this->assertSame('KW/2026/000002', $service->next('kwitansi', '2026-07', 'KW/2026/'));
    }

    public function test_numbers_are_isolated_per_period(): void
    {
        $service = app(DocumentSequenceService::class);

        $this->assertSame('KW/2026/000001', $service->next('kwitansi', '2026-07', 'KW/2026/'));
        $this->assertSame('KW/2026/000001', $service->next('kwitansi', '2026-08', 'KW/2026/'));
        $this->assertSame('KW/2026/000002', $service->next('kwitansi', '2026-07', 'KW/2026/'));
    }

    public function test_different_keys_have_independent_counters(): void
    {
        $service = app(DocumentSequenceService::class);

        $this->assertSame('INV-000001', $service->next('invoice', '2026-07', 'INV-'));
        $this->assertSame('KW-000001', $service->next('kwitansi', '2026-07', 'KW-'));
        $this->assertSame('INV-000002', $service->next('invoice', '2026-07', 'INV-'));
    }

    public function test_persists_next_number_in_the_database(): void
    {
        $service = app(DocumentSequenceService::class);

        $service->next('kwitansi', '2026-07', 'KW/2026/');
        $service->next('kwitansi', '2026-07', 'KW/2026/');
        $service->next('kwitansi', '2026-07', 'KW/2026/');

        $sequence = DocumentSequence::query()
            ->where('key', 'kwitansi')
            ->where('period', '2026-07')
            ->firstOrFail();

        $this->assertSame(4, $sequence->next_number);
        $this->assertSame('KW/2026/', $sequence->prefix);
    }

    public function test_works_inside_a_consuming_transaction(): void
    {
        $service = app(DocumentSequenceService::class);

        $number = DB::transaction(function () use ($service): string {
            return $service->next('kwitansi', '2026-07', 'KW/2026/');
        });

        $this->assertSame('KW/2026/000001', $number);

        $sequence = DocumentSequence::query()->firstOrFail();
        $this->assertSame(2, $sequence->next_number);
    }
}
