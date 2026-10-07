# 05 — HR & Payroll

Module service: `App\Services\Payroll\PayrollService`. Posting rules #9–#11 in
[03-accounting-core.md](03-accounting-core.md) §4. Schema in [02-database-schema.md](02-database-schema.md) §4.

## 1. Employees

Single `employees` table for guru + staff (TU, penjaga, dll.).

- `employment_status`: `pns` / `pppk` / `tetap` / `honorer` / `bsm` (labels Indonesian).
  Rationale for a plain enum over a lookup table: a small school has a handful of stable categories.
- `user_id` (unique, nullable) links staff to their panel login (guru role).
- `base_salary` is duplicated from the GAJI_POKOK component **for display only** — the payroll
  calculation always uses salary components, never `base_salary` directly, so per-employee
  overrides stay explicit.
- BPJS/NPWP numbers stored for slip and BPJS reporting.

## 2. Salary components

Master rows in `salary_components`; per-employee amounts in `employee_salary_components`.

| Code | Type | Calculation | GL mapping |
|---|---|---|---|
| GAJI_POKOK | pendapatan | fixed | 5-1110 |
| TUNJ_JABATAN | pendapatan | fixed | 5-1120 |
| TUNJ_TRANSPORT | pendapatan | fixed | 5-1120 |
| HONOR_PER_JAM | pendapatan | manual entry per payslip (JP × rate) | 5-1130 |
| BONUS_THR | pendapatan | manual entry | 5-1150 |
| BPJS_KES_PEG (1%) | potongan | percent of gaji pokok (capped per regulation) | 2-1200 |
| BPJS_TK_JHT_PEG (2%) | potongan | percent | 2-1200 |
| BPJS_KES_PSH (4%) | pendapatan (employer) | percent | 5-1140 → 2-1200 |
| BPJS_TK_PSH (JKK/JKM/JHT-PJP employer) | pendapatan (employer) | percent | 5-1140 → 2-1200 |
| PPH21 | potongan | manual / TER at approval | 2-1300 |
| POTONGAN_LAIN | potongan | fixed | reduces net (2-1100) |

Percentages live in `percent_rate` (decimal) as **data**, so regulatory changes are edits, not code.
Employer BPJS appears on the payslip as an earnings line (employer share) but is a school cost.

## 3. Payroll run lifecycle

```
draft → calculated → approved → paid
            ↘ cancelled (only from draft/calculated)
```

- **draft** — bendahara creates `payroll_periods` for month M; the system snapshots active
  employees into `payslips`.
- **calculated** — `PayrollService::calculate(period)`: per payslip, pulls
  `employee_salary_components` (+ HONOR manual entries; attendance/leave days as info), writes
  `payslip_items` and totals, aggregates period totals. **Idempotent**: re-calculate wipes and
  rebuilds items while status is still `calculated`.
- **approved** — kepala_sekolah action → posts accrual JE (rule #9). **Locked after this**;
  payslips and components-in-effect are frozen.
- **paid** — bendahara records payment (transfer batch/cash) → posts JE #10; generates payslip
  PDFs (`slip-gaji` blade, A5, per employee; batch ZIP per period is a stretch goal).
- BPJS remittance and PPh 21 deposit are separate actions hitting 2-1200 / 2-1300 (rule #11),
  typically the following month.

## 4. Attendance & leave

- `employee_attendances`: daily input (or simple check-in/out) by operator_tu; feeds payslip info
  blocks (days present/sick/leave/absent) and late reports.
- `leaves`: izin / sakit / cuti / dinas with kepala_sekolah approval (`menunggu → disetujui/ditolak`).
  Approved leave days are reflected on the period's payslip; unpaid leave deductions are a
  `POTONGAN_LAIN`-style component when applicable.
- One attendance row per employee per day (unique constraint) — leave approval updates the
  corresponding attendance rows' status (single source of truth per day).
