<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\Payroll\PayrollService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FundSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SalaryComponentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrPayrollPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            FundSeeder::class,
            AccountSeeder::class,
            SalaryComponentSeeder::class,
        ]);

        AcademicYear::factory()->forStartYear(2026)->create();
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    public function test_super_admin_can_render_every_hr_payroll_page(): void
    {
        $admin = $this->superAdmin();

        $employee = Employee::factory()->create();

        foreach ([
            '/admin/employees',
            '/admin/employees/'.$employee->getKey(),
            '/admin/salary-components',
            '/admin/payroll-periods',
            '/admin/employee-attendances',
            '/admin/leaves',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guru_cannot_access_hr_payroll_pages(): void
    {
        $guru = User::factory()->create();
        $guru->assignRole('guru');

        // A real draft payslip, so the slip route 403s (not 404s) for guru.
        $admin = $this->superAdmin();
        Employee::factory()->create();

        $draft = app(PayrollService::class)->create(2026, 11, $admin);
        $payslip = $draft->payslips()->first();

        foreach ([
            '/admin/salary-components',
            '/admin/payroll-periods',
            '/admin/employee-attendances',
            '/admin/leaves',
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }

        // Employee directory is view-only for guru (hr.employee.view).
        $this->actingAs($guru)->get('/admin/employees')->assertOk();

        $this->actingAs($guru)->get('/payroll/slip/'.$payslip->getKey())->assertForbidden();
    }

    public function test_bendahara_can_download_slip_gaji_after_full_cycle(): void
    {
        $admin = $this->superAdmin();
        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');

        $employee = Employee::factory()->create(['base_salary' => 3_000_000]);

        $components = SalaryComponent::query()
            ->whereIn('code', ['GAJI_POKOK', 'BPJS_KES_PEG', 'BPJS_TK_JHT_PEG'])
            ->get()
            ->keyBy('code');

        foreach (['GAJI_POKOK' => 3_000_000, 'BPJS_KES_PEG' => 0, 'BPJS_TK_JHT_PEG' => 0] as $code => $amount) {
            EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->getKey(),
                'salary_component_id' => $components[$code]->getKey(),
                'amount' => $amount,
            ]);
        }

        $service = app(PayrollService::class);
        $period = $service->create(2026, 10, $admin);
        $period = $service->calculate($period, $admin);
        $period = $service->approve($period, $admin);

        $payslip = $period->payslips()->first();

        $response = $this->actingAs($bendahara)->get('/payroll/slip/'.$payslip->getKey());

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        // Slip is only offered once the period is locked (approved/paid).
        $draft = $service->create(2026, 11, $admin);
        $this->actingAs($bendahara)
            ->get('/payroll/slip/'.$draft->payslips()->first()->getKey())
            ->assertForbidden();
    }
}
