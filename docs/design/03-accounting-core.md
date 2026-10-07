# 03 — Accounting Core

Full double-entry accounting with **accrual basis**. This document defines the chart of accounts,
the journal model, the posting rules for every business event, period handling (tutup buku), and
the financial statement queries.

**Accrual decision**: full accrual with `Piutang Siswa` (receivables). Justification: tunggakan
reporting must tie to the ledger — with accrual, the `1-1300 Piutang Siswa` balance on the Neraca
*is* the sum of outstanding invoices, so billing and the GL can never drift. The extra entry happens
once per monthly batch, so complexity cost is near zero for a small school.

---

## 1. Fund dimension

Indonesian schools must report BOS (government) funds separately from yayasan/komite funds.
**Decision: a `funds` dimension table + `journal_lines.fund_id` — not duplicated COA branches**
(no `1-1101 Kas BOS` style accounts).

- Segmented accounts double/triple the COA and every future report.
- A dimension gives per-fund statements ("Laporan Realisasi Dana BOS") with a single
  `WHERE fund_id = ?` on the same queries.
- Lines without a fund mean "yayasan/konsolidasi".

Seed funds:

| Code | Name | Type |
|---|---|---|
| `BOS` | Dana BOS | pemerintah |
| `YYS` | Yayasan | yayasan |
| `KOM` | Komite/Komunitas | komite |
| `UMUM` | Umum/Konsolidasi | lainnya |

Many accounts carry a `default_fund_id` so postings pre-fill the dimension (BOS revenue → fund BOS).

---

## 2. Chart of accounts (COA seed)

Structure: `1-xxxx` aset, `2-xxxx` kewajiban, `3-xxxx` ekuitas/dana, `4-xxxx` pendapatan, `5-xxxx` beban.
Header rows (`is_header = true`) exist for report grouping and are **never postable**.
`cash_flow_category` drives the Arus Kas statement.

| Code | Name | Type | Normal | Cash flow | Default fund |
|---|---|---|---|---|---|
| 1-0000 | ASET | header | debit | — | — |
| 1-1000 | Aset Lancar | header | debit | — | — |
| 1-1100 | Kas | aset | debit | non_kas | UMUM |
| 1-1150 | Kas Kecil | aset | debit | non_kas | UMUM |
| 1-1200 | Bank | aset | debit | non_kas | *(set per cash_account line)* |
| 1-1300 | Piutang Siswa | aset | debit | non_kas | — |
| 1-1400 | Perlengkapan (Persediaan ATK) | aset | debit | non_kas | UMUM |
| 1-1500 | Biaya Dibayar di Muka | aset | debit | non_kas | UMUM |
| 1-2000 | Aset Tetap | header | debit | — | — |
| 1-2100 | Tanah | aset | debit | investasi | — |
| 1-2200 | Gedung & Bangunan | aset | debit | investasi | — |
| 1-2300 | Peralatan & Mesin | aset | debit | investasi | — |
| 1-2400 | Meubelair (Perabot) | aset | debit | investasi | — |
| 1-2500 | Buku Perpustakaan | aset | debit | investasi | — |
| 1-2900 | Akumulasi Penyusutan | aset (kontra) | **kredit** | non_kas | — |
| 2-0000 | KEWAJIBAN | header | kredit | — | — |
| 2-1100 | Utang Gaji | kewajiban | kredit | operasi | — |
| 2-1200 | Utang BPJS | kewajiban | kredit | operasi | — |
| 2-1300 | Utang PPh 21 | kewajiban | kredit | operasi | — |
| 2-1400 | Utang Usaha / Vendor | kewajiban | kredit | operasi | — |
| 2-1500 | Pendapatan Diterima di Muka | kewajiban | kredit | operasi | — |
| 3-0000 | EKUITAS / DANA | header | kredit | — | — |
| 3-1100 | Saldo Dana Awal (Ekuitas) | ekuitas | kredit | non_kas | *(via dimension)* |
| 3-1200 | Surplus / Defisit Tahun Berjalan | ekuitas | kredit | non_kas | — |
| 4-0000 | PENDAPATAN | header | kredit | — | — |
| 4-1100 | Pendapatan SPP | pendapatan | kredit | operasi | KOM |
| 4-1200 | Pendapatan DSP | pendapatan | kredit | operasi | KOM |
| 4-1300 | Pendapatan Dana BOS | pendapatan | kredit | operasi | BOS |
| 4-1400 | Pendapatan Seragam | pendapatan | kredit | operasi | KOM |
| 4-1500 | Pendapatan Buku & LKS | pendapatan | kredit | operasi | KOM |
| 4-1600 | Pendapatan Kegiatan | pendapatan | kredit | operasi | KOM |
| 4-1700 | Pendapatan Donasi / Infaq | pendapatan | kredit | operasi | YYS |
| 4-1900 | Pendapatan Lain-lain | pendapatan | kredit | operasi | YYS |
| 5-0000 | BEBAN | header | debit | — | — |
| 5-1110 | Beban Gaji Pokok | beban | debit | operasi | YYS |
| 5-1120 | Beban Tunjangan | beban | debit | operasi | YYS |
| 5-1130 | Beban Honor | beban | debit | operasi | YYS |
| 5-1140 | Beban BPJS (bagian sekolah) | beban | debit | operasi | YYS |
| 5-1150 | Beban Bonus / THR | beban | debit | operasi | YYS |
| 5-1300 | Beban Penyusutan | beban | debit | operasi | *(follow asset fund)* |
| 5-1400 | Beban Perlengkapan & ATK | beban | debit | operasi | *(follow issuance)* |
| 5-1500 | Beban Beasiswa & Potongan | beban | debit | operasi | KOM |
| 5-1600 | Beban Kegiatan Siswa | beban | debit | operasi | *(follow event)* |
| 5-1710 | Beban Listrik, Air & Telepon | beban | debit | operasi | — |
| 5-1720 | Beban Internet & Langganan | beban | debit | operasi | — |
| 5-1730 | Beban Kebersihan & Keamanan | beban | debit | operasi | — |
| 5-1800 | Beban Perawatan & Perbaikan | beban | debit | operasi | — |
| 5-1900 | Beban Administrasi & Lain-lain | beban | debit | operasi | — |
| 5-1950 | Beban/Selisih Penghapusan Aset | beban | debit | investasi | *(follow asset fund)* |

The seed ships as `AccountSeeder`; all rows `is_locked = true` except user-added 5-xxxx expense
accounts. New expense accounts may be added by bendahara/super_admin; other ranges are system-owned.

---

## 3. Journal model

`journal_entries` (header) + `journal_lines` (min 2 lines). Columns: [02-database-schema.md](02-database-schema.md).

### 3.1 The only write path

```
App\Services\Accounting\JournalPostingService::post(JournalDraft $draft): JournalEntry
```

Validation order (each failure throws `AccountingException` with an Indonesian message):
1. `entry_date` must be ≤ today — `Tanggal jurnal tidak boleh di masa depan.`
2. Period for `entry_date` must exist and be `open` — `Periode Agustus 2026 sudah ditutup.` /
   `Tidak ada periode akuntansi untuk Juli 2026.`
3. Every account must be postable: `is_active`, not `is_header` — `Akun 1-0000 adalah akun grup dan tidak dapat diposting.`
4. `sum(debit) == sum(credit)` and both `> 0` — `Total debit harus sama dengan total kredit.`

Then, inside `DB::transaction` with the period row locked (`lockForUpdate`, to race against tutup buku):
insert the entry (number from `document_sequences`, `JE/{yyyy-mm}/{n}`) and its lines.

### 3.2 Balance invariant — three layers (all three)

1. **Service layer (primary)** — as above; the only code path that writes journals.
2. **MySQL CHECK constraint** on `journal_lines` (enforced by MySQL ≥ 8.0.16):
   `CHECK (debit >= 0 AND credit >= 0 AND NOT (debit > 0 AND credit > 0))`.
   A per-*entry* balance cannot be expressed as a row-level constraint — do **not** use triggers.
3. **Reconciliation commands** — `accounting:verify-balance` scheduled daily
   (`…HAVING SUM(debit) <> SUM(credit)` must return zero rows, else notify super_admin),
   and `accounting:verify-trial-balance` as part of tutup buku.

### 3.3 Immutability & correction

- Once `status = posted`: no UPDATE/DELETE via application code.
- Corrections = **void** the entry: `JournalPostingService::void(entry, reason)` creates a mirrored
  reversal entry linked via `voided_by_entry_id`, subject to the same period rules.
- An entry created by a business event (payment, payroll, …) may only be voided by voiding the
  business document first — services enforce this ordering.
- Kwitansi and journal numbers are **never reused**.

---

## 4. Posting rules — every business event

One journal entry per business transaction (batched where noted). Fund dimension on each line comes
from the source document (fee fund, asset fund, event fund) or the account's `default_fund_id`.

| # | Business event (trigger) | DEBIT | CREDIT | Notes |
|---|---|---|---|---|
| 1 | Invoice batch issued | 1-1300 Piutang Siswa (gross) | 4-11xx per-fee revenue accounts (per item totals) | One JE per batch (12/year), not per invoice |
| 2 | Discount on issued invoice | 5-1500 Beban Beasiswa & Potongan | 1-1300 Piutang Siswa | Booked within the batch JE (potongan items) or on ad-hoc discount approval |
| 3 | Payment received | 1-1100 / 1-1150 / 1-1200 (via `cash_accounts.account_id`) | 1-1300 Piutang Siswa | Fund follows the invoice's fee fund |
| 4 | Payment voided | mirror of #3 | mirror | Reversal JE; kwitansi number never reused |
| 5 | Bad-debt write-off (hapusbuku) | 5-1900 Beban Administrasi & Lain-lain | 1-1300 | super_admin only; invoice → `void` with reason |
| 6 | Expense recorded (manual) | 5-xxxx beban account | 1-1100 Kas / 1-1200 Bank / 2-1400 Utang Vendor | Generic form; account + fund pickers required |
| 7 | Consumable purchase (stock masuk) | 1-1400 Perlengkapan | Kas/Bank | Weighted-avg cost updated on `inventory_items` |
| 8 | Consumable issuance (stock keluar) | 5-1400 (or 5-1600 for kegiatan) | 1-1400 Perlengkapan | Expense recognized at issuance: stock value ≡ 1-1400 balance |
| 9 | Payroll approved (accrual) | 5-1110…5-1150 (gross + employer BPJS) | 2-1100 Utang Gaji (net) / 2-1200 Utang BPJS / 2-1300 Utang PPh 21 | One JE per payroll period |
| 10 | Payroll paid | 2-1100 Utang Gaji | 1-1200 Bank / 1-1100 Kas | Per payslip batch; slip PDF per employee |
| 11 | BPJS & PPh 21 remitted | 2-1200 / 2-1300 | 1-1200 Bank | Via expense form (liability settlement mode) or dedicated action |
| 12 | Asset acquired | 1-21xx category asset account | Kas/Bank (or 2-1400 if credit) | Fund dimension = `assets.fund_id` |
| 13 | Monthly depreciation run | 5-1300 Beban Penyusutan | 1-2900 Akumulasi Penyusutan | One JE per run; per-line fund = each asset's fund |
| 14 | Asset disposal **with** proceeds | Kas/Bank (proceeds) + 1-2900 (accumulated) + 5-1950 (loss, if any) | 1-21xx (cost) + 4-1900 (gain, if any) | Single JE computed by `AssetDisposalService` |
| 15 | Asset disposal **without** proceeds | 1-2900 (accumulated) + 5-1950 (net book value) | 1-21xx (cost) | Same service |
| 16 | BOS funds received | 1-1200 Bank | 4-1300 Pendapatan BOS | "Terima Dana BOS" action; fund BOS on both lines |
| 17 | Tutup buku (period close) | 4-11xx…4-19xx (all revenue balances) → and then 3-1200 | 3-1200 Surplus/Defisit ← and then 5-xxxx (all expense balances) | Auto closing JE; see §5 |

Manual journal entry (jurnal umum) is allowed for bendahara + super_admin for anything not
covered — same `post()` path, `source = manual`.

### The Pengeluaran / transaction form (no `expenses` table)

Rules #6, #11, and #16 are captured through the *Pengeluaran & Penerimaan* Filament form — a
structured front-end over manual journal posting, **not** a separate `expenses` table. The form
has three modes, all ending in one `JournalPostingService::post()` call (`source = manual`,
optional polymorphic reference):

- **Beban** (#6): expense account picker (5-xxxx) + fund picker + payment source
  (Kas / Bank / Utang Vendor) + optional **event link** (posts with the event's fund; this is how
  `events` and RKAS realization tie back to the GL).
- **Setelan utang** (#11): liability account picker (2-1200 / 2-1300 / 2-1400) + payment source.
- **Penerimaan dana** (#16): revenue account picker (4-xxxx) + cash account — e.g. BOS receipt.

Rationale: a separate table would duplicate the journal and risk drifting from it; the journal
entry *is* the expense document, and the list/report UI reads `journal_entries` filtered by
`source = manual` and account type.

---

## 5. Period handling & tutup buku

- `accounting_periods` are seeded automatically when an academic year is created: 12 rows,
  `2026-07` … `2027-06`. Posting to a date outside any period is rejected.
- Default state: current and past months `open`; `post()` rejects `entry_date > today` regardless.
- **Tutup buku flow** (bendahara/super_admin clicks *Tutup Buku* on a month):
  1. Integrity checks: all JE in the month balanced; no draft invoices/batches/payroll for the month;
     trial balance computed and equal.
  2. Post the closing JE (rule #17): close all revenue accounts into 3-1200, then all expense
     accounts into 3-1200.
  3. Set period status `closed`, record `closed_at` / `closed_by`.
- Closed periods reject all new/edited postings with `Periode Agustus 2026 sudah ditutup.`
- **Reopen** = super_admin only: unposts the closing JE (void), re-opens the period, writes an
  activity-log entry.
- Filament page *Periode Akuntansi*: all periods with net movement, JE count, and action buttons.

---

## 6. Financial statement queries (exact definitions)

Notation: `bal(account, from, to)` = `SUM(debit) − SUM(credit)` for the account in range, adjusted
by the account's `normal_balance` sign. Opening balance = the sum over all entries with
`entry_date < from`. Header accounts aggregate their descendants; only postable accounts carry balances.

### 6.1 Neraca Saldo (Trial Balance)
Per postable account: `opening` (entries before `from`), `mutasi_debit` / `mutasi_credit` in range,
`ending = opening ± mutasi`. Filters: date range, fund. Totals row must show Σ debit = Σ credit
(asserted in code).

### 6.2 Buku Besar (General Ledger)
Journal lines per account in range with running balance; each line links to its `journal_entries`
for drill-down.

### 6.3 Laba Rugi (Income Statement)
Revenue accounts (`pendapatan`): `SUM(credit) − SUM(debit)` in range. Expense accounts (`beban`):
`SUM(debit) − SUM(credit)`. Surplus/defisit = revenue − expenses. Grouped by header account.
**Optional fund filter turns this into the BOS realization report** (`fund_id = BOS`).

### 6.4 Neraca (Balance Sheet)
As at a date: assets = ending debit-normal balances of `1-xxxx` (1-2900 is credit-normal and
subtracted); liabilities = ending credit balances of `2-xxxx`; equity = 3-1100 (saldo awal)
+ 3-1200 + current-year surplus (revenue − expenses year-to-date). The page asserts
**A = K + E** exactly (integer money, so zero tolerance) and refuses to render otherwise.

### 6.5 Arus Kas (Cash Flow) — direct method
All `journal_lines` where `account_id ∈ {1-1100, 1-1150, 1-1200}` in range. For each such line,
classify the **sibling line** (same entry, non-cash account) by its `cash_flow_category`:
operasi / investasi / pendanaan. Sum per category; opening and closing cash per `cash_account`.
This is why `cash_flow_category` lives on `accounts` — no second classification table.

### 6.6 Laporan Realisasi Dana BOS
School-mandated report: Laba Rugi filtered `fund_id = BOS` + Neraca filtered `fund_id = BOS`
+ quarterly realization vs RKAS. Exported as PDF (shared `laporan-keuangan` blade).

All statements render both in Filament pages (with filters) and as PDF exports.

---

## 7. Bank reconciliation (enhancement — roadmap Phase 11)

Purpose: the book balance of each `cash_accounts` register can drift from the real bank/kas
statement between manual checks; reconciliation verifies it line by line.

- **Import**: bank statement CSV → `bank_statement_lines` rows
  ([02-database-schema.md](02-database-schema.md) §2): date, description, amount (statement sign:
  in positive / out negative), bank reference. One import per cash account + period.
- **Matching**: pairing page per cash account — statement lines against unreconciled
  `journal_lines` on the account. Defaults: exact amount, date window ±7 days; manual override;
  many-to-one allowed (one statement line = several JE lines, e.g. bundled transfers).
  Status per statement line: `unmatched` → `matched` / `booked`.
- **Differences**: bank charges, interest, and unrecorded items are booked via the manual journal
  path (rule #6: Dr 5-1900 / Cr 1-1200 for a charge) and matched to their statement line. Unmatched
  lines carry forward to the next reconciliation.
- **Invariant**: per cash account, book balance − Σ unreconciled differences = statement closing
  balance at the import date; the page shows the reconciliation gap per account.
- **Out of scope**: live bank feeds (bank API agreements) — CSV import only.

Manual kas (cash) accounts are reconciled the same way against the physical cash-opname result.
