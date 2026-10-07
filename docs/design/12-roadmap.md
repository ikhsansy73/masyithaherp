# 12 — Implementation Roadmap

Ordered phases; each ends in a merged, tested, demo-able state. Phases 1–10 are the core build;
**enhancement phases 11–14** (from the [market comparison](13-market-comparison.md) backlog) follow,
and Phase 11 may be pulled forward to directly follow Phase 2. Tests are PHPUnit **feature** tests
under `tests/Feature/` (project convention), unit tests for calculators under `tests/Unit/`.
The design source of truth for each phase is the linked module doc.

## Phase overview

| # | Phase | Design doc | Scope |
|---|---|---|---|
| 1 | Foundation & Auth | 01, 09, 10, 11 | MySQL + locale, academic years/terms, funds, sequences, users/roles, panel skeletons, settings, activity log |
| 2 | Accounting Core | 03 | COA seed, periods, JournalPostingService, manual journals, tutup buku, 4 statements + verify commands |
| 3 | Students & Classes | 06 | Students/guardians, rombel, enrollments, movements wizard, attendance, subjects, schedules, PPDB, calendar |
| 4 | Billing & Payments | 04 | Fee chain, batch generation, invoices, discounts, payments + kwitansi + tunggakan |
| 5 | HR & Payroll | 05 | Employees, components, payroll lifecycle, attendance/leave, payslip PDF |
| 6 | Assets & Inventory | 07 | Registry, depreciation command, disposal, maintenance, opname, ATK |
| 7 | Academics & Rapor | 06 | CP/TP, teaching journals, assessments + scores, rapor generation + workflow + publish |
| 8 | Parent Portal | 10 | Full `/portal` |
| 9 | Operations | 08 | RKAS, surat, rapat, kegiatan, pengumuman |
| 10 | Dashboards & Polish | 10 | Widgets, PDF exports, demo seeders, backups |

## Phases in detail

### Phase 1 — Foundation & Auth
**Scope**: `.env` → MySQL (Laragon); locale/tz; install packages (doc 11); migrations for foundation
tables; `AcademicYearService` (auto-seeds terms + 12 periods); `document_sequences` with
`lockForUpdate`; spatie permission setup + `PermissionSeeder` (all roles & permissions from doc 09);
two panel skeletons with `canAccessPanel`; `SchoolSettings`; activity log wired; demo user per role.

**Acceptance**: login as each role lands on the right panel; navigation filtered per role;
Tahun Ajaran CRUD auto-creates terms/periods; settings editable.

**Tests**: `RolePanelAccessTest` (each role → correct panel or 403) · `AcademicYearTest` ·
`DocumentSequenceTest` (concurrency-safe increments, per-year reset).

### Phase 2 — Accounting Core
**Scope**: `AccountSeeder` (doc 03 COA); `JournalPostingService` + `JournalDraft` DTO;
CHECK constraint on `journal_lines`; manual journal Filament resource; period page + tutup buku +
reopen; statement pages (Neraca Saldo, Buku Besar, Laba Rugi, Neraca, Arus Kas);
`accounting:verify-balance` scheduled command.

**Acceptance**: a balanced manual entry posts and appears in all four statements; an unbalanced
entry is rejected with the Indonesian error; posting into a closed period is rejected; tutup buku
produces the closing JE and locks the month.

**Tests**: `JournalBalanceInvariantTest` (balanced posts; unbalanced throws; zero-total throws;
header-account throws) · `PeriodClosingTest` (post-after-close throws; reopen works) ·
`TrialBalanceTest` (opening + mutation + ending) · `CashFlowClassificationTest`.

### Phase 3 — Students & Classes
**Scope**: students/guardians resources (media photos); rombel + enrollments; promotion wizard
(`student_movements`); attendance input page (class grid); subjects, class-subject-teachers,
schedules; calendar days; PPDB resource + Daftarkan action (`PpdbService`).

**Acceptance**: PPDB accept creates student + guardians + enrollment + student_fees in one action;
promotion wizard rolls a class into the next year (naik/tinggal/lulus/mutasi); attendance grid saves
with uniqueness.

**Tests**: `StudentCreationFromPpdbTest` · `PromotionWizardTest` (all movement types) ·
`AttendanceUniquenessTest` (one row per student/day) · `PortalParentScopingTest` (parent sees only
own children — guards Phase 8).

### Phase 4 — Billing & Payments
**Scope**: Kas & Bank (cash accounts) resource + seeds; fee types/structures/student fees resources;
`InvoiceBatchService` + scheduled `billing:generate-invoices`; invoice resource + viewer; discounts;
`PaymentService` + payment form with FIFO pre-fill and override; kwitansi PDF; tunggakan page +
aging + widget; void flows.

**Acceptance**: monthly batch generates one invoice per active SPP student and posts one batch JE;
FIFO allocation works with manual override; partial payments allowed, overpayment blocked; kwitansi
PDF downloads; tunggakan aging buckets correct.

**Tests**: `InvoiceBatchGenerationTest` (count, amounts, JE balanced, idempotent rerun) ·
`PaymentAllocationTest` (FIFO order, manual override, partial, overpayment blocked, void reverses
JE + allocations) · `TunggakanAgingTest`.

### Phase 5 — HR & Payroll
**Scope**: employees resource (user linking); salary components + per-employee config; payroll
period resource with Hitung/Setujui/Bayar actions (`PayrollService`); attendance + leave resources
with approval; slip gaji PDF.

**Acceptance**: full cycle draft→calculated→approved→paid posts the two correct JEs; component math
(BPJS %, employer share) correct; slips download; approved periods are locked.

**Tests**: `PayrollCalculationTest` (earnings/deductions/employer BPJS/net vs fixture employee) ·
`PayrollWorkflowTest` (approve posts JE #9, pay posts #10, locked after approve) ·
`LeaveAffectsPayslipTest`.

### Phase 6 — Assets & Inventory
**Scope**: categories + locations + assets resources (acquisition JE, duplicate action);
`DepreciationService` + scheduled `assets:depreciate` + run button; disposal service + UI;
maintenance; opname run UI; ATK inventory + stock movements (weighted avg).

**Acceptance**: asset create posts JE #12; monthly run produces capped straight-line rows + JE #13
and is idempotent; disposal computes gain/loss correctly; stock value always equals 1-1400 balance.

**Tests**: `DepreciationRunTest` (amount, NBV cap, idempotent unique(asset,period), closed period) ·
`AssetDisposalTest` (with/without proceeds, gain vs loss lines) · `ConsumableCostingTest`.

### Phase 7 — Academics & Rapor
**Scope**: CP seeder (BSKAP data) + TP authoring UI; teaching journals; assessments + input nilai
page; `FinalScoreCalculator`; `ReportCardService::generate` with auto-descriptions; rapor workflow
(diajukan/revisi/disetujui/diterbitkan) + bulk PDF.

**Acceptance**: finals compute 40/60; rapor draft auto-descriptions are editable; workflow state
machine enforced (wali kelas cannot approve); published rapor visible to parents (portal readiness);
rapor PDF prints per class.

**Tests**: `RaporWorkflowTest` (full state machine, permission denials) ·
`FinalScoreComputationTest` (unit) · `RaporDescriptionGenerationTest`.

### Phase 8 — Parent Portal
**Scope**: complete `/portal`: Anak Saya, Tagihan, Riwayat Pembayaran (+ kwitansi), Rapor (published
only), Absensi calendar, Jadwal, Pengumuman; database notifications.

**Acceptance**: parent journey end-to-end: login → see child's invoice → see paid kwitansi → see
published rapor PDF; nothing writable; no cross-family data leaks.

**Tests**: `PortalEndToEndTest` (HTTP feature test) · `PortalRaporVisibilityTest` (hidden until
`diterbitkan`).

### Phase 9 — Operations
**Scope**: RKAS page (live realization queries); surat masuk/keluar with numbering + letterhead PDF;
rapat + notulen + berita acara; kegiatan with budget realization; pengumuman with audience +
notifications; calendar day effects on attendance input.

**Acceptance**: RKAS shows planned vs realized pulling real journal data; surat keluar numbers
sequential per year; pengumuman audience-scoped (wali_murid sees only their audience).

**Tests**: `RkasRealizationTest` (journal lines aggregated per account+fund) ·
`OutgoingLetterSequenceTest` · `AnnouncementAudienceTest`.

### Phase 10 — Dashboards, Reports & Polish
**Scope**: all admin dashboard widgets; laporan PDF exports for every statement; demo seeders
(1 academic year, 6 rombel, ~60 students, employees, full-year journals & billing);
spatie/laravel-backup scheduled; performance pass; Pint + full suite green.

**Acceptance**: demo-ready install; every dashboard widget renders < 500ms on seeded data; backups
run on schedule.

**Tests**: `StatementEquationTest` (A = K + E on seeded data) · `GoldenYearScenarioTest`
(seed → full year of events → trial balance balances; surplus matches Laba Rugi) ·
navigation smoke test per role.

## Enhancement phases (from the market-comparison backlog, [13-market-comparison.md](13-market-comparison.md) §9)

Ordered by priority from the benchmark. Phase 11 may be **pulled forward to directly follow
Phase 2** (it only needs the accounting core); the rest assume the core build.

| # | Phase | Design doc | Backlog item |
|---|---|---|---|
| 11 | Bank Reconciliation | 03 §7 | gap #3 |
| 12 | Payments & Communications | 04 §6, 13 | gaps #2, #4 |
| 13 | Government Integrations | 08, 13 | gap #6 |
| 14 | Progressive Web App | 10, 13 | gap #1 (first step) |

### Phase 11 — Bank Reconciliation
**Scope**: `bank_statement_lines` ([02-database-schema.md](02-database-schema.md) §2) + CSV import
(date, description, amount, bank ref); matching page pairing statement lines against unreconciled
`journal_lines` on each cash account (amount exact, date window, manual override, many-to-one for
bundled transfers); difference booking via manual JE (rule #6 path); per-account reconciled-balance
report. Design: [03-accounting-core.md](03-accounting-core.md) §7.

**Acceptance**: import a statement, match payments/deposits, book a bank-charge difference;
book balance − reconciled differences = statement balance.

**Tests**: `BankStatementImportTest` · `ReconciliationMatchingTest` (exact/window/manual, idempotent
status transitions) · `DifferenceBookingTest`.

### Phase 12 — Payments & Communications
**Scope**: Midtrans Snap driver implementing `PaymentSource` → `PaymentService::record()`
(`method = qris`, `reference` filled), signed + idempotent webhook endpoint, invoice payment
deep-links in `/portal`; pluggable WhatsApp notification channel (Fonnte/Wablas-style driver,
queued jobs) with templates for: tagihan terbit, pembayaran diterima + kwitansi link, tunggakan
reminder (aging cron), pengumuman fan-out; per-guardian opt-out flag.

**Acceptance**: sandbox payment via webhook creates payment + allocations + JE exactly once;
WhatsApp messages queue on all four events; opt-out respected.

**Tests**: `MidtransWebhookTest` (signature, replay idempotency, amount mismatch) ·
`PaymentGatewayDriverTest` · `WhatsAppChannelTest` (fake driver) · `TunggakanReminderCommandTest`.

### Phase 13 — Government Integrations
**Scope**: Dapodik CSV exports (peserta didik, PTK/guru, rombongan belajar — field-mapping
config); ARKAS-format RKAS export (planned vs realized, mapped to BOS codes) with
year/term/level filters; export pages + permissions.

**Acceptance**: exports match Dapodik/ARKAS template columns; RKAS realization figures tie to
`journal_lines` exactly.

**Tests**: `DapodikExportTest` (fixture files) · `ArkasExportTest` (realization = journal sums).

### Phase 14 — Progressive Web App
**Scope**: PWA manifest + service worker for `/portal` (installable, offline shell, cache
strategy), web-push groundwork; `/admin` unaffected.

**Acceptance**: `/portal` installable on a phone home screen; cached portal pages render offline.

**Tests**: HTTP smoke tests (manifest/service worker present, correct scope).

### Future backlog (not scheduled — triggers noted)
- **Procurement-lite** (PO → utang vendor → settlement; rule #6 already books utang) — when purchasing control is wanted.
- **Tabungan siswa** (student savings, APPSO-style) — the most-requested local feature; savings accounts per student + deposit/withdraw journaling.
- **Transport / library / canteen modules** — only if requirements grow.
- **Multi-branch / yayasan group consolidation** — far future; the fund dimension already covers most in-school reporting.
- **AI/analytics extras** — not planned.

## Cross-cutting engineering rules

- Every service method that writes financial data is `DB::transaction`-wrapped and tested for both
  success and the **exact Indonesian exception message** (`App\Exceptions\AccountingException` etc.).
- Factories for every model from the phase that introduces it; seeders per module where masters exist.
- `vendor/bin/pint --dirty` before finalizing any change; run the phase's tests plus
  `php artisan test --compact --filter=<Phase>` before merge.
- Migrations follow doc 02 exactly; deviations require updating doc 02 in the same change.
- New permissions land in `PermissionSeeder` in the same phase as the feature that needs them.
