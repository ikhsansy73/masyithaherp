# 07 — Assets & Inventory

Module services: `App\Services\Assets\DepreciationService`, `AssetDisposalService`.
Posting rules #12–#15 in [03-accounting-core.md](03-accounting-core.md) §4.

## 1. Asset registry

- **One row = one physical unit** — quantity lives in the world, not in the DB. This makes opname
  and penghapusan honest.
- `code` is the printed inventory label (`INV-Aset/2026/000007`); QR label printing via a print
  blade is a stretch goal.
- Category (`asset_categories`) carries the GL mapping (`asset_account_id` → 1-2100…1-2500) and
  default `useful_life_months`; `is_depreciable = false` for tanah (and optionally buku).
- Acquisition form (category, cost, fund, funding_source label, location, custodian) posts JE #12
  on save. **"Duplikat aset"** action clones a unit row with a new code for bulk purchases.
- `fund_id` is the accounting dimension; `funding_source` is a descriptive label (bos/yayasan/
  komite/hibah).
- Status: `aktif` → `dijual` / `dihapuskan` / `hilang`.

## 2. Depreciation

- Monthly scheduled command `assets:depreciate {period}` (also a *Jalankan Penyusutan* button on
  the Periode Akuntansi page).
- For each depreciable, active asset whose life spans the period — straight line:
  `amount = (cost − salvage) / useful_life_months`, **capped at remaining net book value**.
- Inserts `asset_depreciations` rows — unique `(asset_id, accounting_period_id)` makes runs
  **idempotent/rerunnable**; skips assets already depreciated for the period; respects closed periods.
- One JE per run (rule #13): Dr 5-1300 / Cr 1-2900, per-line fund = each asset's fund.

## 3. Disposal & loss

- `AssetDisposalService` computes NBV (cost − accumulated depreciation) and posts a single JE:
  - **with proceeds** (dijual): Dr Kas/Bank (proceeds) + Dr 1-2900 (accumulated) + Dr 5-1950 (loss,
    if any) / Cr 1-21xx (cost) + Cr 4-1900 (gain, if any) — rule #14;
  - **without proceeds** (dihapuskan/hilang): Dr 1-2900 + Dr 5-1950 (NBV) / Cr 1-21xx — rule #15.
- `hilang` requires a supporting opname finding (`asset_opname_items.found = false`).
- Sets `status`, `disposal_date`, `disposal_method`, `disposal_proceeds`.

## 4. Maintenance

- `asset_maintenances` records perawatan/perbaikan with vendor and cost.
- Cost is **expensed to 5-1800, never capitalized** (rule #6 path) — school repairs are small and
  capitalization thresholds invite arguments.

## 5. Opname (stocktaking)

- Create an `asset_opnames` run → per-location check-off of assets into `asset_opname_items`
  (found? condition changed?).
- Mismatches drive updates (condition) or movements (`hilang` status + JE via disposal service).

## 6. Consumables (barang habis pakai / ATK)

Included by design — `inventory_items` + `stock_movements` with **weighted-average costing**:

- Purchase (stock `masuk`): Dr 1-1400 Perlengkapan / Cr Kas-Bank (rule #7); avg cost recomputed.
- Issuance (stock `keluar`): Dr 5-1400 (or 5-1600 for kegiatan) / Cr 1-1400 (rule #8).
- Invariant: stock × avg_cost ≡ 1-1400 balance — the Neraca's Perlengkapan line always equals
  physical stock value, and ATK spending shows where it's used.
- If the school later finds per-unit tracking fussy, switching to "expense on purchase" is a
  one-account mapping change (buy straight to 5-1400), not a redesign.
- `min_stock` triggers a low-stock alert widget for operator_tu.
