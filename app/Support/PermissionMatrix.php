<?php

namespace App\Support;

/**
 * The role → permission matrix from docs/design/09-auth-roles.md §3.
 * Consumed by PermissionSeeder (creates permission rows) and
 * RoleSeeder (creates roles and syncs their permissions).
 */
class PermissionMatrix
{
    public const ROLES = [
        'super_admin',
        'kepala_sekolah',
        'bendahara',
        'operator_tu',
        'guru',
        'wali_kelas',
        'wali_murid',
    ];

    /**
     * @return array<string, list<string>>
     */
    public static function build(): array
    {
        $matrix = array_fill_keys(self::ROLES, []);

        // --- Keuangan ---
        static::crud($matrix, 'accounting.coa', full: ['bendahara'], view: ['kepala_sekolah']);
        static::crud($matrix, 'accounting.journal', full: ['bendahara'], view: ['kepala_sekolah']);
        static::crud($matrix, 'accounting.period', full: ['bendahara'], view: ['kepala_sekolah']);
        static::action($matrix, 'accounting.period.reopen', ['super_admin']);
        static::action($matrix, 'accounting.report.view', ['kepala_sekolah', 'bendahara']);
        static::crud($matrix, 'billing.fee', full: ['bendahara'], view: ['kepala_sekolah', 'operator_tu']);
        static::crud($matrix, 'billing.batch', full: ['bendahara']);
        static::crud($matrix, 'billing.invoice', full: ['bendahara'], view: ['kepala_sekolah', 'operator_tu', 'wali_murid']);
        static::crud($matrix, 'billing.payment', full: ['bendahara', 'operator_tu'], view: ['kepala_sekolah', 'wali_murid']);
        static::crud($matrix, 'billing.transaction', full: ['bendahara'], view: ['kepala_sekolah', 'operator_tu']);
        static::action($matrix, 'billing.void', ['bendahara']);
        static::action($matrix, 'billing.arrears.view', [
            'kepala_sekolah', 'bendahara', 'operator_tu', 'guru', 'wali_kelas', 'wali_murid',
        ]);

        // --- SDM & Penggajian ---
        static::crud($matrix, 'hr.employee', full: ['operator_tu'], view: ['kepala_sekolah', 'bendahara', 'guru']);
        static::crud($matrix, 'hr.attendance', full: ['operator_tu'], view: ['kepala_sekolah', 'bendahara']);
        static::crud($matrix, 'hr.leave', full: ['operator_tu'], view: ['kepala_sekolah', 'bendahara']);
        static::action($matrix, 'payroll.calculate', ['bendahara']);
        static::action($matrix, 'payroll.approve', ['kepala_sekolah']);
        static::action($matrix, 'payroll.pay', ['bendahara']);
        static::action($matrix, 'payroll.view', ['kepala_sekolah']);

        // --- Siswa, PPDB & Akademik ---
        static::crud($matrix, 'academics.year', full: ['operator_tu'], view: ['kepala_sekolah', 'bendahara', 'guru', 'wali_kelas']);
        static::crud($matrix, 'students.student', full: ['operator_tu'], view: ['kepala_sekolah', 'bendahara', 'guru', 'wali_kelas', 'wali_murid']);
        static::crud($matrix, 'ppdb', full: ['operator_tu'], view: ['kepala_sekolah']);
        static::crud($matrix, 'academics.classroom', full: ['operator_tu'], view: ['kepala_sekolah', 'guru', 'wali_kelas', 'wali_murid']);
        static::crud($matrix, 'academics.curriculum', full: ['kepala_sekolah'], view: ['operator_tu', 'guru', 'wali_kelas']);
        static::crud($matrix, 'academics.journal', full: ['guru', 'wali_kelas'], view: ['kepala_sekolah']);
        static::crud($matrix, 'academics.assessment', full: ['guru', 'wali_kelas'], view: ['kepala_sekolah']);
        static::crud($matrix, 'attendance.student', full: ['operator_tu', 'guru', 'wali_kelas'], view: ['kepala_sekolah', 'wali_murid']);
        static::crud($matrix, 'rapor.input', full: ['guru', 'wali_kelas'], view: ['kepala_sekolah']);
        static::action($matrix, 'rapor.submit', ['wali_kelas']);
        static::action($matrix, 'rapor.approve', ['kepala_sekolah']);
        static::action($matrix, 'rapor.publish', ['kepala_sekolah']);
        static::action($matrix, 'rapor.view', ['kepala_sekolah', 'guru', 'wali_kelas', 'wali_murid']);

        // --- Aset & Inventaris ---
        static::crud($matrix, 'assets.asset', full: ['operator_tu'], view: ['kepala_sekolah', 'bendahara']);
        static::action($matrix, 'assets.depreciation.run', ['bendahara']);
        static::action($matrix, 'assets.disposal', ['bendahara']);
        static::crud($matrix, 'inventory.stock', full: ['bendahara', 'operator_tu'], view: ['kepala_sekolah']);

        // --- Operasional ---
        static::crud($matrix, 'operations.rkas', full: ['kepala_sekolah', 'bendahara']);
        static::crud($matrix, 'operations.surat', full: ['operator_tu'], view: ['kepala_sekolah']);
        static::crud($matrix, 'operations.meeting', full: ['kepala_sekolah', 'operator_tu'], view: ['bendahara', 'guru', 'wali_kelas']);
        static::crud($matrix, 'operations.event', full: ['kepala_sekolah', 'operator_tu'], view: ['bendahara', 'guru', 'wali_kelas']);
        static::crud($matrix, 'operations.announcement', full: ['operator_tu'], view: ['kepala_sekolah', 'guru', 'wali_kelas', 'wali_murid']);
        static::crud($matrix, 'operations.calendar', full: ['operator_tu'], view: ['kepala_sekolah', 'guru', 'wali_kelas']);

        // --- Pengaturan ---
        static::crud($matrix, 'system.user', full: ['super_admin']);
        static::crud($matrix, 'system.role', full: ['super_admin']);
        static::crud($matrix, 'system.settings', full: ['super_admin']);

        // super_admin always owns the complete set, so it stays in sync
        // as new permissions are added to the matrix above.
        $matrix['super_admin'] = collect($matrix)->flatten()->unique()->values()->all();

        return $matrix;
    }

    /**
     * Grant full CRUD verbs; "own" scopes are enforced later by policies,
     * not by the permission itself (doc 09 §4).
     *
     * @param  array<string, list<string>>  $matrix
     * @param  list<string>  $full
     * @param  list<string>  $view
     * @param  list<string>  $own
     */
    private static function crud(array &$matrix, string $base, array $full = [], array $view = [], array $own = []): void
    {
        foreach ($full as $role) {
            $matrix[$role] = [...$matrix[$role], ...static::verbs($base, ['viewAny', 'view', 'create', 'update', 'delete'])];
        }

        foreach ($view as $role) {
            $matrix[$role] = [...$matrix[$role], ...static::verbs($base, ['viewAny', 'view'])];
        }

        foreach ($own as $role) {
            $matrix[$role] = [...$matrix[$role], ...static::verbs($base, ['viewAny', 'view', 'create', 'update', 'delete'])];
        }
    }

    /**
     * Grant a non-CRUD action permission verbatim.
     *
     * @param  array<string, list<string>>  $matrix
     * @param  list<string>  $roles
     */
    private static function action(array &$matrix, string $name, array $roles): void
    {
        foreach ($roles as $role) {
            $matrix[$role] = [...$matrix[$role], $name];
        }
    }

    /**
     * @param  list<string>  $verbs
     * @return list<string>
     */
    private static function verbs(string $base, array $verbs): array
    {
        return array_map(fn (string $verb): string => "{$base}.{$verb}", $verbs);
    }
}
