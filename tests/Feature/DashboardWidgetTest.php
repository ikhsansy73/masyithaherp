<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceItemType;
use App\Models\Classroom;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Services\School\AcademicYearService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, FundSeeder::class, AccountSeeder::class]);

        app(AcademicYearService::class)->create('2026/2027', isDefault: true);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function enrolledStudent(string $letter = 'A'): Student
    {
        $year = \App\Models\AcademicYear::query()->where('is_default', true)->firstOrFail();
        $classroom = Classroom::factory()->grade(1, $letter)->create([
            'academic_year_id' => $year->getKey(),
        ]);

        $student = Student::factory()->create();

        $student->enrollments()->create([
            'academic_year_id' => $year->getKey(),
            'classroom_id' => $classroom->getKey(),
            'grade_level' => $classroom->grade_level,
            'status' => EnrollmentStatus::Aktif,
        ]);

        return $student;
    }

    private function overdueInvoice(Student $student, int $daysOverdue, int $total): Invoice
    {
        $year = \App\Models\AcademicYear::query()->where('is_default', true)->firstOrFail();

        $invoice = Invoice::factory()->issued()->create([
            'student_id' => $student->getKey(),
            'academic_year_id' => $year->getKey(),
            'due_date' => Carbon::today()->subDays($daysOverdue),
            'total' => $total,
            'paid_amount' => 0,
            'period_month' => 7,
        ]);

        $invoice->items()->create([
            'item_type' => InvoiceItemType::Posisi,
            'description' => 'SPP bulan 7',
            'amount' => $total,
        ]);

        return $invoice;
    }

    public function test_super_admin_sees_stats_and_both_widgets(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Siswa Aktif')
            ->assertSee('Total Tunggakan')
            ->assertSee('Stok Menipis')
            ->assertSee('Tunggakan Teratas')
            ->assertSee('Stok ATK Menipis');
    }

    public function test_guru_does_not_see_inventory_widgets(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        $this->actingAs($guru)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Tunggakan Teratas')
            ->assertDontSee('Stok ATK Menipis')
            ->assertDontSee('Stok Menipis');
    }

    public function test_dashboard_lists_top_arrears_and_low_stock_items(): void
    {
        $student = $this->enrolledStudent('A');
        $this->overdueInvoice($student, 15, 600_000);

        InventoryItem::factory()->lowStock()->create([
            'name' => 'Spidol Whiteboard',
            'code' => 'ATK-SPW',
        ]);

        $this->actingAs($this->superAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Spidol Whiteboard')
            ->assertSee('ATK-SPW')
            ->assertSee('600.000');
    }

    public function test_stats_and_widget_reports_render_without_data(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Tidak ada tunggakan.')
            ->assertSee('Semua stok aman.');
    }
}
