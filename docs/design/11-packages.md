# 11 — Packages

Every dependency change requires approval (CLAUDE.md) — this list is the approved plan.
Installed once, in roadmap Phase 1 ([12-roadmap.md](12-roadmap.md)).

## Use

| Package | Version | Why (one line) |
|---|---|---|
| filament/filament | ^5.0 | The admin framework; bundles Livewire ^4 + Tailwind ^4, supports Laravel 12 |
| spatie/laravel-permission | ^6.0 | 7-role matrix + navigation gating is exactly its job |
| spatie/laravel-activitylog | ^4.x | Financial audit trail (journals, payments, invoices, payroll, assets, rapor) is non-negotiable for a bendahara system |
| spatie/laravel-medialibrary | ^11.x | Student/employee photos, surat scans, receipts, leave attachments — avoids hand-rolled uploads |
| spatie/laravel-settings | ^3.x | School profile + configurable thresholds (predikat, SPP due day, formula weights) with zero admin-UI plumbing |
| barryvdh/laravel-dompdf | ^3.x | All PDF artifacts (kwitansi, rapor, slip, surat, laporan) share one engine and blade skills |
| caresome/filament-auth-designer | ^3.1 | Styled login screen + theme toggle on both panels (user-requested addition at Phase 1) |

## Skip (decided against)

| Package | Why |
|---|---|
| spatie/laravel-data | Filament form objects + plain DTO classes cover it; DTO frameworks earn their keep on public APIs, which we don't have |
| spatie/laravel-model-status | Plain string columns + backed enums + service-level transition checks are more type-safe and debuggable |
| filament/spatie-laravel-settings-plugin (shield-style plugins) | Not needed; settings form is one custom page |
| maatwebsite/laravel-excel | Filament's built-in CSV export covers reporting needs; xlsx is a future nicety |

## Later (not in initial phases)

| Package | When | Why |
|---|---|---|
| spatie/laravel-backup | Before go-live (Phase 10) | A finance system without DB backups is a liability — schedule `mysqldump` dumps |
| midtrans/midtrans-php (or HTTP client) | When online payments are approved | Gateway driver plugs into `PaymentService::record(PaymentSource)`; `payments.reference` column already reserved |
| Whether to add Filament Shield | Never, unless roles explode | Hand-curated `PermissionSeeder` is clearer at 7 roles |

## Frontend

- Tailwind CSS ^4 + Vite ^7 (already in the skeleton) — Filament v5's stack; no extra bundler work.
- No Alpine direct usage (Filament/Livewire manage it).
