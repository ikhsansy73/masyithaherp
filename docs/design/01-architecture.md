# 01 — Architecture & Global Conventions

Binding conventions for every module of Masyithah. Any implementation that deviates from this
document must update it first.

## 1. Project overview

Masyithah is the finance & ERP platform for SD Masyithah, an Indonesian elementary school
(grades 1–6). It records every financial transaction of the school and manages the surrounding
operations: students & enrollment (PPDB), classes & curriculum (Kurikulum Merdeka), HR & payroll,
assets, and school operations — with portals for staff and parents (wali murid).

Single school, no multi-tenancy. Users: roughly 10–30 staff, up to ~300 students, ~600 parent accounts.

## 2. Stack

| Layer | Choice | Version | Notes |
|---|---|---|---|
| Framework | Laravel | 12.x (skeleton installed at 12.47) | |
| Admin framework | Filament | ^5.0 | Bundles Livewire ^4.0, Tailwind CSS ^4.1; supports Laravel 12 |
| Database | MySQL 8.x (design) — **MariaDB 11.3.2 as installed** | — | Via Laragon. MariaDB is a MySQL drop-in (utf8mb4, `lockForUpdate`, CHECK constraints all supported); accepted deviation noted at Phase 1. `.env` switches from the skeleton's sqlite at Phase 1 |
| PHP | 8.3 | — | |
| Tests | PHPUnit | ^11.5 | Feature tests in `tests/Feature/`, unit tests for calculators |
| PDF | barryvdh/laravel-dompdf | ^3 | Kwitansi, payslip, rapor, surat, laporan |
| Auth/permissions | spatie/laravel-permission | ^6 | See [09-auth-roles.md](09-auth-roles.md) |
| Audit | spatie/laravel-activitylog | ^4 | See [11-packages.md](11-packages.md) |
| Media | spatie/laravel-medialibrary | ^11 | Photos, surat scans, receipts |
| Settings | spatie/laravel-settings | ^3 | School profile + configurable thresholds |

Package rationale: [11-packages.md](11-packages.md).

Filament v5 layout (as implemented): admin resources/pages live in `app/Filament/Resources` and
`app/Filament/Pages` (v5 default discovery); the parent portal has its own tree at
`app/Filament/Portal/{Resources,Pages,Widgets}`. Both panels share `app/Models`, `app/Services`,
and `app/Support`. Note for this PHP build: internal interfaces in redeclared property types must
be fully qualified (`string | \UnitEnum | null $navigationGroup`) — unqualified names resolve to
the current namespace and fail the invariance check.

## 3. Global conventions

### 3.1 Money

- **Rupiah as integers.** All monetary columns are `BIGINT` (default `0`), never `decimal`/`float`.
  IDR has no practical minor unit; integers avoid rounding bugs in allocations and payroll.
- Rendering via `App\Support\Money::format(int $rupiah): string` → `Rp1.250.000`
  (`NumberFormatter` with `id_ID`, no decimals).
- Signed values appear only in report computations; stored debit/credit columns are non-negative
  (enforced by the `journal_lines` CHECK constraint — see [03-accounting-core.md](03-accounting-core.md)).

### 3.2 Locale & time

- `config/app.php`: `'locale' => 'id'`, `'faker_locale' => 'id_ID'`, `'timezone' => 'Asia/Jakarta'`.
- Business dates (invoice date, entry date, attendance date) are **date-only** columns.
- All UI strings in Bahasa Indonesia (Filament labels, validation messages, exceptions).

### 3.3 Enums

- PHP 8.3 backed **string** enums in `app/Enums/`, stored as string columns
  (`$table->string('status')` + `'status' => InvoiceStatus::class` cast).
- No MySQL `ENUM` column type. No spatie/laravel-model-status — transitions are guarded in services.
- Enum case names: `TitleCase` values, `snake_case` backed values (e.g. `InvoiceStatus::PartiallyPaid = 'partially_paid'`).
- User-facing labels live in a `label(): string` method on the enum, in Indonesian.

### 3.4 Keys & identifiers

- Every table: surrogate `id` bigint auto-increment.
- Natural keys are **unique columns, never primary keys**: `students.nis`, `accounts.code`,
  document numbers (`invoices.number`, `payments.number`, …).
- FKs: `$table->foreignId('x_id')->constrained()` — `cascadeOnDelete()` only for true dependents
  (journal lines, invoice items, payslip items, assessment scores); `restrictOnDelete()` otherwise.

### 3.5 Soft deletes

Only on: `invoices`, `payments`, `journal_entries`, `students`, `employees`.
Financial correction is **void-and-reverse, never delete** — soft delete is a safety net, not a workflow.

### 3.6 Document numbering

One `document_sequences` table; `next_number` incremented with `lockForUpdate()` inside the
creating transaction (concurrency-safe; see [02-database-schema.md](02-database-schema.md)).

| Key | Format | Example |
|---|---|---|
| invoice | `INV/{tahun-ajaran}/{n}` | `INV/2026-2027/000123` |
| kwitansi | `KW/{yyyy}/{n}` | `KW/2026/000042` |
| journal entry | `JE/{yyyy-mm}/{n}` | `JE/2026-07/000318` |
| surat keluar | `SK/{yyyy}/{n}` | `SK/2026/000015` |
| PPDB | `PPDB/{yyyy}/{n}` | `PPDB/2026/000087` |
| asset | `INV-Aset/{yyyy}/{n}` | `INV-Aset/2026/000007` |

Zero-padding to at least 6 digits; sequences reset per period (`period` column, e.g. `2026`).

### 3.7 Business logic placement

- Business logic lives in **service classes** under `app/Services/<Module>/`
  (e.g. `app/Services/Billing/PaymentService.php`).
- Filament Resources, Artisan commands, and queued jobs all call the same services.
  No controllers except thin print/download routes (PDF).
- **Only `App\Services\Accounting\JournalPostingService` may insert `journal_entries` / `journal_lines`.**
  No model observers write journals.
- Every service method that writes financial data is wrapped in `DB::transaction`.
- Reserved service names for later phases:
  - `App\Services\Accounting\JournalPostingService`
  - `App\Services\Billing\PaymentService` (+ `StudentFeeService`, `InvoiceBatchService`)
  - `App\Services\Payroll\PayrollService`
  - `App\Services\Academics\ReportCardService`
  - `App\Services\Assets\AssetDisposalService`

### 3.8 Exceptions & user messages

- Domain exceptions in `app/Exceptions/` (e.g. `AccountingException`, `BillingException`).
- Messages are user-facing **Bahasa Indonesia**:
  - `Periode Agustus 2026 sudah ditutup.`
  - `Total debit harus sama dengan total kredit.`
  - `Pembayaran melebihi sisa tagihan.`
- Filament surfaces them via `Notification::danger()` / validation errors.

### 3.9 Audit trail

`spatie/laravel-activitylog` (`Spatie\Activitylog\Traits\LogsActivity` + `$activitylog` properties)
on financial and sensitive models only: `journal_entries`, `payments`, `invoices`,
`payroll_periods`, `assets` (status changes), `report_cards`, `students`, `employees`, `discounts`.
Not on high-churn tables (attendance rows, assessment scores).

### 3.10 Caching & queues

- Queue connection: `database` (default). Queued jobs for PDF batch generation and future
  notification fan-out only; journal posting is always synchronous inside the triggering transaction.
- Cache: `database`. No external cache server required.

## 4. Code organization

Standard Laravel 12 structure; no domain folders.

```
app/
  Enums/            # backed string enums, one file per enum
  Models/           # Eloquent models (casts() method style)
  Services/
    Accounting/     # JournalPostingService, PeriodService, statement query builders
    Billing/        # StudentFeeService, InvoiceBatchService, PaymentService
    Payroll/        # PayrollService
    Academics/      # ReportCardService, FinalScoreCalculator, PromotionService
    Assets/         # DepreciationService, AssetDisposalService
    School/         # PpdbService, StudentMovementService
  Policies/         # row-level scoping (see 09)
  Support/          # Money, Terbilang, AcademicYear helper
  Filament/
    Admin/          # Resources, Pages, Widgets for /admin panel
    Portal/         # Resources, Pages for /portal panel
  Exceptions/       # AccountingException, BillingException, ...
```

- Models use `casts()` method (Laravel 11+ style), not the `$casts` property.
- Factories for **every** model from the phase that introduces it (CLAUDE.md mandate).
- Form Requests for any non-Filament HTTP input; Filament resources use their own form system.

## 5. Configuration rules

- `env()` only inside `config/` files; application code reads `config(...)`.
- School-configuration values (formula weights, predicate thresholds, SPP due day, signature names)
  live in **spatie settings** (`SchoolSettings`, `AcademicSettings`), editable in the panel —
  not scattered in config files.
