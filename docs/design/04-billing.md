# 04 — Billing: SPP, Tagihan, Pembayaran, Tunggakan

Module services: `App\Services\Billing\StudentFeeService`, `InvoiceBatchService`, `PaymentService`.
Posting rules referenced below are defined in [03-accounting-core.md](03-accounting-core.md) §4.

## 1. Configuration chain

```
fee_types (what)                      SPP, DSP, SERAGAM, BUKU, KEGIATAN, WISUDA
  └─ fee_structures (how much)        per academic year × grade (null grade = flat), + fund
      └─ student_fees (penetapan)     per student per year, cloned from structures, editable per student
          └─ invoices (tagihan)       monthly for SPP, one-off for tahunan/insidental
              └─ invoice_items        posisi (charges) + potongan (discounts)
          └─ payments (kwitansi)  →   payment_allocations → invoices
```

Seeded fee types:

| Code | Name | Category | Months | Revenue account | Default fund |
|---|---|---|---|---|---|
| SPP | SPP (Sumbangan Pembinaan Pendidikan) | bulanan | 12 | 4-1100 | KOM |
| DSP | DSP (Sumbangan Pengembangan Pendidikan) | tahunan | 1 | 4-1200 | KOM |
| SERAGAM | Seragam | insidental | 1 | 4-1400 | KOM |
| BUKU | Buku & LKS | tahunan | 1 | 4-1500 | KOM |
| KEGIATAN | Kegiatan | tahunan | 1 | 4-1600 | KOM |
| WISUDA | Wisuda | insidental | 1 | 4-1600 | KOM |

`fee_structures` per grade allow e.g. SPP kelas 1 = 150.000, kelas 4 = 175.000; a `grade_level = null`
row is the flat default.

At enrollment (PPDB acceptance) or year-start, `StudentFeeService::assignDefaultFees(student, year)`
clones matching structures into `student_fees`. Bendahara may adjust a student's fee (exception
case — e.g. siblings, mid-year entry via `first_month`).

## 2. Batch invoice generation (SPP)

- Monthly **scheduled command** `billing:generate-invoices {year} {month}` — runs on the 20th for
  the next month; also triggerable from the Filament *Batch Tagihan* page.
- Flow: create `invoice_batches` (status `draft`) → for each active student with an active SPP
  `student_fee`: one `invoice` (`number INV/2026-2027/000123`, `period_month`), items from
  `student_fees` minus eligible `discounts` (each discount inserted as an `item_type = potongan` row).
- **Draft batches are freely editable/deletable.** Review screen shows total, count, and per-class
  exceptions. The **Terbitkan** action: sets `issued` on batch + invoices and posts **one journal
  entry per batch** (posting rule #1 + #2). Issued batches are only voidable (void → mirror JE).
- Tahunan/insidental fees (DSP, BUKU, …) are invoiced manually per student or in a one-off batch —
  same machinery with `period_month = null`.
- Idempotency: the unique constraint on `(academic_year_id, fee_type_id, period_month)` prevents
  duplicate batches; the generator skips students who already hold an invoice for the fee+month.

## 3. Invoice states

```
draft → issued → partially_paid → paid
draft → cancelled
issued / partially_paid → void   (reversal JE; with reason)
```

- `paid_amount` is a **derived cache** written only by `PaymentService::allocate()`/`deallocate()`.
- Enforced invariants: allocations never exceed `invoice.total − invoice.paid_amount` nor
  `payment.amount − already allocated`; no allocation against `draft`/`void`/`cancelled` invoices.

## 4. Payments & kwitansi

Recording flow (bendahara or operator_tu, permission-gated):

1. Pick student → screen lists outstanding invoices sorted **oldest due first**, with remaining
   amounts, discount flags, and aging.
2. Enter: amount, method (`tunai`/`transfer`/`qris`/`ewallet`/`lainnya`), cash account, date, notes.
3. Allocation is **pre-filled FIFO by due date** across `issued`/`partially_paid` invoices and is
   **manually overridable** per line.
4. One transaction (`PaymentService::record(PaymentSource $dto)`):
   create `payments` (kwitansi number from `document_sequences`, `KW/{yyyy}/{n}`) →
   insert `payment_allocations` → update invoice statuses → post JE #3.
5. **Cetak Kwitansi**: dompdf PDF, A5, two copies (asli/arsip), with `App\Support\Terbilang::make()`
   spelled-out amount, kwitansi number, method, and per-invoice allocation table.

Rules:

- Partial payments allowed; **overpayment blocked** (`Pembayaran melebihi sisa tagihan.`) — the
  error suggests recording the excess as a separate payment or posting it to 2-1500 manually.
- Unallocated remainders surface in a *Pembayaran belum dialokasikan* widget.
- Void payment (permission-gated): reversal payment row (`reversed_by_payment_id`) + reversal JE +
  allocations removed + invoice statuses recomputed. The original kwitansi number stays consumed.

## 5. Tunggakan (arrears) reporting

- Core query: invoices `issued`/`partially_paid` past `due_date`; aging buckets
  **1–30 / 31–60 / 61–90 / >90 days**; grouped per student, per classroom, per grade; totals per
  academic year.
- Filament page *Laporan Tunggakan* with filters (kelas, bulan, status beasiswa) + PDF export
  (`daftar-tunggakan` blade) for surat pemberitahuan to wali murid.
- Dashboard widget *Tunggakan Teratas*: top 10 students by outstanding, with kelas + days overdue.
- Students with an active `discounts` row are flagged so bendahara doesn't chase exempt families.

## 6. Payment-gateway-proofing (Midtrans later — not built this round)

- `PaymentService::record()` takes a `PaymentSource` DTO (manual driver today: bendahara input).
- `payments.reference` (nullable string) is reserved now for the future gateway reference.
- A future Midtrans integration adds a `payment_intents` table and a driver that calls the same
  service with `method = qris, reference = …` — **no schema change needed today**.
