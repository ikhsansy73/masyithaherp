# 09 — Auth, Roles & Permissions

spatie/laravel-permission ^6 (table set in [02-database-schema.md](02-database-schema.md) §1).
No Filament Shield — permissions are hand-curated via `PermissionSeeder` for clarity at 7 roles.

## 1. Roles

| Role | Who | Panel |
|---|---|---|
| `super_admin` | System owner / IT | `/admin` — everything |
| `kepala_sekolah` | Principal | `/admin` — full read, approve payroll & rapor |
| `bendahara` | Treasurer | `/admin` — owns finance operations |
| `operator_tu` | Admin staff (TU) | `/admin` — master data, PPDB, correspondence, attendance |
| `guru` | Teacher | `/admin` — academics for own classes only |
| `wali_kelas` | Homeroom teacher | `/admin` — guru + rapor workflow for own class |
| `wali_murid` | Parent | `/portal` — read-only, own children |

Roles are additive in practice: a teacher who is also wali kelas holds both `guru` and `wali_kelas`.

Panel access: `User::canAccessPanel(Panel $panel)` — staff roles → `admin`, `wali_murid` → `portal`.
`is_active = false` users are rejected at login and by the panel check.

## 2. Permission naming

`module.action` strings, reference: `App\Support\PermissionMatrix` (source of truth, consumed by
`PermissionSeeder` for the `permissions` table and `RoleSeeder` for `roles` + sync — one seeder
per table). CRUD verbs: `view`, `viewAny`, `create`, `update`, `delete`.
Seeded by `PermissionSeeder`; assigned to roles by name. New resources must add their permissions
to `App\Support\PermissionMatrix` in the same phase.

## 3. Permission matrix

Legend: **F** = full (CRUD) · **V** = view · **own** = row-scoped to own classes/children · — = none.

| Module / capability | super_admin | kepala_sekolah | bendahara | operator_tu | guru | wali_kelas | wali_murid |
|---|---|---|---|---|---|---|---|
| Dashboard keuangan widgets | F | V | F | V | — | — | — |
| COA, jurnal umum, periode, tutup buku | F | V | F | — | — | — | — |
| Laporan keuangan (NS, BB, LR, Neraca, Arus Kas, BOS) | F | F | F | — | — | — | — |
| Jenis/struktur/penetapan biaya | F | V | F | V | — | — | — |
| Generate batch tagihan | F | — | F | — | — | — | — |
| Catat pembayaran + kwitansi | F | V | F | F | — | — | — |
| Pengeluaran & penerimaan kas (form transaksi) | F | V | F | V | — | — | — |
| Void pembayaran / hapusbuku piutang | F | V | F | — | — | — | — |
| Laporan tunggakan | F | V | F | V | — | own kelas | own anak |
| Karyawan master data | F | V | V | V | own | — | — |
| Payroll: hitung (calculate) | F | V | F | — | — | — | — |
| Payroll: setujui (approve) | F | **F** | — | — | — | — | — |
| Payroll: bayar + slip gaji | F | V | F | — | — | — | — |
| Absensi & izin karyawan | F | V | V | F | — | — | — |
| Siswa master + wali | F | V | V | F | own kelas | own kelas | — |
| PPDB | F | V | — | F | — | — | — |
| Rombel, jadwal, mapel | F | V | — | F | V | V | own anak |
| Kurikulum CP/TP | F | F | — | V | V | V | — |
| Jurnal mengajar | F | V | — | — | F own | F own | — |
| Input penilaian/nilai | F | V | — | — | F own | F own | — |
| Rapor: input nilai mapel & deskripsi | F | V | — | — | F own | F own | — |
| Rapor: ajukan (submit) | F | V | — | — | — | **F** own kelas | — |
| Rapor: setujui (approve) | F | **F** | — | — | — | — | — |
| Rapor: terbitkan (publish) | F | **F** | — | — | — | — | — |
| Rapor: lihat (terbit) | F | F | — | — | own kelas | own kelas | **own anak** |
| Absensi siswa: input | F | V | — | F | F own kelas | F own kelas | — |
| Absensi siswa: lihat | F | V | — | F | V own kelas | V own kelas | V own anak |
| Aset & inventaris | F | V | V | F | — | — | — |
| Penyusutan run & penghapusan aset | F | V | F | — | — | — | — |
| ATK stock movements | F | V | F | F | — | — | — |
| RKAS | F | F | F | — | — | — | — |
| Surat masuk/keluar | F | V | — | F | — | — | — |
| Rapat & notulen | F | F | V | F | V | V | — |
| Kegiatan | F | F | V | F | V | V | — |
| Pengumuman | F | F | — | F | V | V | V (audience) |
| Kalender pendidikan | F | F | — | F | V | V | — |
| Users, roles, settings sekolah | **F** | — | — | — | — | — | — |

## 4. Two-layer authorization

1. **Permissions** (matrix above) gate Filament resources, navigation items, page/action visibility,
   and route access.
2. **Policies** add **row-level scoping** where the matrix says *own*:
   - `ClassroomPolicy` — guru/wali_kelas ↔ `class_subject_teachers` or homeroom assignment;
   - `ReportCardPolicy` — wali kelas of the classroom (all states), kepala_sekolah (any);
   - `StudentPolicy` — portal parent ↔ `guardians.user_id`;
   - `InvoicePolicy` / `PaymentPolicy` — portal parent via the student relation.
   - Policies return **false** for staff-only rows they don't own; they never widen permissions.

Row-scoped queries are implemented as an Eloquent trait — `App\Models\Concerns\ScopedToOwnClassrooms`
— applied in each resource's `getEloquentQuery()` (Filament ownership filtering), not in global
scopes that would surprise other code paths.

## 5. Guard

Single guard (`web`). spatie tables use the default team-free setup — role uniqueness is global,
which is correct for a single school.
