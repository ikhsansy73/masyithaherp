# Masyithah — Design Documentation

**Platform Keuangan & ERP SD Masyithah** — Laravel 12 + Filament v5 + Spatie + MySQL.

This directory is the single source of truth for the system design. Implementation rounds execute
against these documents module by module. Phase 1 (Foundation & Auth) is implemented; see the
status table below.

## Document map

| # | Document | What it covers |
|---|----------|----------------|
| 01 | [Architecture](01-architecture.md) | Stack, global conventions (money, locale, enums, keys, sequences), code organization |
| 02 | [Database Schema](02-database-schema.md) | Complete table inventory (~79 tables) with columns, constraints, module ERDs |
| 03 | [Accounting Core](03-accounting-core.md) | COA, journal design, posting rules, periods & tutup buku, financial statements, funds |
| 04 | [Billing](04-billing.md) | SPP & fee management, batch invoicing, payments & kwitansi, tunggakan |
| 05 | [HR & Payroll](05-hr-payroll.md) | Employees, salary components, payroll lifecycle, attendance & leave |
| 06 | [Students & Academics](06-students-academics.md) | Students, PPDB, rombel, attendance, Kurikulum Merdeka (CP→TP), assessments, rapor |
| 07 | [Assets & Inventory](07-assets-inventory.md) | Asset registry, depreciation, disposal, opname, consumables (ATK) |
| 08 | [Operations](08-operations.md) | RKAS, surat masuk/keluar, rapat, kegiatan, pengumuman |
| 09 | [Auth & Roles](09-auth-roles.md) | 7 roles, permission matrix, policies & row-level scoping |
| 10 | [Filament Panels](10-filament-panels.md) | `/admin` + `/portal` panels, resources, dashboard widgets, PDF strategy |
| 11 | [Packages](11-packages.md) | Spatie & support packages: use / skip / later, with versions |
| 12 | [Roadmap](12-roadmap.md) | 10 implementation phases, acceptance criteria, test plan |
| 13 | [Market Comparison](13-market-comparison.md) | Cloud school ERPs (iSAMS, Fedena, ERPNext, Indonesian SaaS…) benchmarked against our design: features, finance depth, UI, pros/cons, backlog |

## Reading order

- **For implementation rounds**: read [01-architecture.md](01-architecture.md) (conventions are binding),
  then the module doc(s) for the phase you are building, with [02-database-schema.md](02-database-schema.md)
  and [03-accounting-core.md](03-accounting-core.md) as reference.
- **For reviewers**: [01](01-architecture.md) → [03](03-accounting-core.md) → [09](09-auth-roles.md) → [12](12-roadmap.md)
  gives the complete decision picture in four files.

## Key decisions at a glance

- **Full double-entry accounting** with accrual basis (Piutang Siswa); business events auto-post journals.
- **Rupiah as BIGINT integers** everywhere; never decimal.
- **Fund dimension** (`funds` + `journal_lines.fund_id`) for BOS / Yayasan / Komite tracking — not duplicated COA branches.
- **Manual payment recording** (kwitansi PDF); schema is payment-gateway-ready (Midtrans later).
- **UI in Bahasa Indonesia**; docs in English with Indonesian domain terms.
- **Two Filament panels**: `/admin` (staff, permission-scoped) and `/portal` (wali murid, read-only).

## Status

| Date | Change |
|------|--------|
| 2026-10-03 | Initial complete design (docs 01–12) |
| 2026-10-03 | Market comparison vs cloud school ERPs added (doc 13, iSAMS-focused) — includes prioritized backlog for future rounds |
| 2026-10-03 | Comparison backlog folded into roadmap as enhancement Phases 11–14; bank reconciliation designed (docs 02 §2, 03 §7) |
| 2026-10-07 | **Phase 1 (Foundation & Auth) implemented**: packages installed (incl. caresome/filament-auth-designer), foundation migrations, `AcademicYearService` (auto-seeds 2 terms + 12 periods), `document_sequences` with `lockForUpdate`, spatie permission matrix (`App\Support\PermissionMatrix`) + per-table seeders, two panels with `canAccessPanel`, `SchoolSettings` + ProfilSekolah page, Tahun Ajaran & Pengguna resources; 26 tests passing. Deviations: MariaDB 11.3.2 (Laragon), `\UnitEnum`/`\BackedEnum` qualified in property types |
