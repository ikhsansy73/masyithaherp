<?php

namespace Tests\Feature;

use App\Enums\InvoiceItemType;
use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Models\AcademicYear;
use App\Models\CashAccount;
use App\Models\Discount;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\InvoiceBatch;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_super_admin_can_render_every_billing_page(): void
    {
        $admin = $this->superAdmin();

        foreach ([
            '/admin/cash-accounts',
            '/admin/fee-types',
            '/admin/fee-structures',
            '/admin/student-fees',
            '/admin/discounts',
            '/admin/invoice-batches',
            '/admin/invoices',
            '/admin/payments',
            '/admin/tunggakan',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guru_role_only_sees_tunggakan(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        foreach ([
            '/admin/cash-accounts',
            '/admin/fee-types',
            '/admin/fee-structures',
            '/admin/student-fees',
            '/admin/discounts',
            '/admin/invoice-batches',
            '/admin/invoices',
            '/admin/payments',
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }

        $this->actingAs($guru)->get('/admin/tunggakan')->assertOk();
        $this->actingAs($guru)->get('/billing/daftar-tunggakan')->assertOk();
    }

    public function test_billing_pages_render_with_rows(): void
    {
        $admin = $this->superAdmin();

        $feeType = FeeType::factory()->create();
        $year = AcademicYear::factory()->create();

        StudentFee::factory()->create(['fee_type_id' => $feeType->getKey(), 'academic_year_id' => $year->getKey()]);
        CashAccount::factory()->create();
        FeeStructure::factory()->create(['academic_year_id' => $year->getKey()]);
        Discount::factory()->create(['academic_year_id' => $year->getKey()]);
        InvoiceBatch::factory()->create(['academic_year_id' => $year->getKey()]);
        Invoice::factory()->issued()->create(['academic_year_id' => $year->getKey()]);
        Payment::factory()->create();

        foreach ([
            '/admin/cash-accounts',
            '/admin/fee-types',
            '/admin/fee-structures',
            '/admin/student-fees',
            '/admin/discounts',
            '/admin/invoice-batches',
            '/admin/invoices',
            '/admin/payments',
            '/admin/tunggakan',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_delete_guard_tracks_each_model_usage(): void
    {
        $this->actingAs($this->superAdmin());

        $feeType = FeeType::factory()->create();
        $studentFee = StudentFee::factory()->create(['fee_type_id' => $feeType->getKey()]);

        $this->assertTrue(FeeTypeResource::billingFeeResponse('delete', $feeType)->allowed());
        $this->assertTrue(FeeTypeResource::billingFeeResponse('delete', $studentFee)->allowed());

        $invoice = Invoice::factory()->issued()->create([
            'student_id' => $studentFee->student_id,
            'academic_year_id' => $studentFee->academic_year_id,
        ]);
        $invoice->items()->create([
            'item_type' => InvoiceItemType::Posisi,
            'fee_type_id' => $studentFee->fee_type_id,
            'description' => 'SPP',
            'amount' => $invoice->total,
        ]);

        $feeTypeDenial = FeeTypeResource::billingFeeResponse('delete', $feeType);
        $this->assertFalse($feeTypeDenial->allowed());
        $this->assertSame('Jenis biaya sudah dipakai pada tagihan dan tidak dapat dihapus.', $feeTypeDenial->message());

        $feeDenial = FeeTypeResource::billingFeeResponse('delete', $studentFee);
        $this->assertFalse($feeDenial->allowed());
        $this->assertSame('Biaya siswa sudah menghasilkan tagihan dan tidak dapat dihapus.', $feeDenial->message());
    }
}
