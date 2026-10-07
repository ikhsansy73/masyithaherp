# 10 — Filament v5 Panel Architecture

**Decision: exactly two panels.**

1. **`/admin`** — all staff roles. One panel + permission-scoped navigation beats N role-specific
   panels: fewer plugins/resources to duplicate, and Filament navigation groups hide themselves per
   permission cleanly. A guru logging into `/admin` sees only the *Akademik* group.
2. **`/portal`** — `wali_murid` only. A separate panel because the information architecture and
   tone differ entirely (read-only, child-centric). Same `User` model; `canAccessPanel` checks role.

Login: single `/login`; after auth, staff land on `/admin`, parents on `/portal`.
Password reset via email when SMTP is configured, otherwise operator_tu resets in *Pengguna*.

Default dark/light theming stays Filament-standard; brand color configured once per panel.

## 1. `/admin` navigation (labels in Bahasa Indonesia)

| Group | Items |
|---|---|
| **Akademik** | Tahun Ajaran, Semester, Mata Pelajaran, Rombel (Kelas), Jadwal Pelajaran, CP — Capaian Pembelajaran, TP — Tujuan Pembelajaran, Jurnal Mengajar, Penilaian, Input Nilai (custom page), Rapor (+ Aksi Generate & workflow) |
| **Keuangan** | Akun (COA), Jurnal Umum (+ viewer detail), Periode Akuntansi (incl. Tutup Buku), Bidang Dana, Kas & Bank, Jenis Biaya, Struktur Biaya, Penetapan Biaya Siswa, Batch Tagihan (+ Generate), Tagihan, Beasiswa & Potongan, Pembayaran (+ Cetak Kwitansi), Pengeluaran (expense form); **Laporan** pages: Neraca Saldo, Buku Besar, Laba Rugi, Neraca, Arus Kas, Tunggakan, Realisasi Dana BOS |
| **Siswa & PPDB** | Siswa, PPDB, Penempatan (enrollments), Pergerakan Siswa (+ wizard kenaikan), Absensi Siswa (input page), Kalender Pendidikan, Wali Murid |
| **SDM & Penggajian** | Karyawan, Komponen Gaji, Komponen per Karyawan, Periode Payroll (+ Hitung / Setujui / Bayar), Slip Gaji (PDF), Absensi Karyawan, Izin & Cuti |
| **Aset & Inventaris** | Kategori Aset, Ruang/Lokasi, Aset (+ Akuisisi / Penghapusan / Duplikat), Log Penyusutan, Pemeliharaan, Opname Aset, Barang Habis Pakai, Mutasi Stok |
| **Operasional** | RKAS, Surat Masuk, Surat Keluar, Rapat, Kegiatan, Pengumuman |
| **Pengaturan** | Pengguna, Peran & Izin, Profil Sekolah (settings form: kop surat, signature names, thresholds) |

Notes:
- Custom Filament **pages** (not resources): laporan keuangan ×7, Input Nilai, Absensi Siswa input,
  Input Absensi (grid), wizard pages (PPDB convert, promotion, tutup buku confirmation).
- Every resource's `getEloquentQuery()` applies the scoping trait / policies from
  [09-auth-roles.md](09-auth-roles.md).
- The expense form (Pengeluaran) posts rule #6; it is the generic entry point for beban/utang
  transactions including BPJS/PPh remittance and BOS receipt (#11, #16).

## 2. `/admin` dashboard widgets

| Widget | Content |
|---|---|
| `StatsKasOverview` | 4 stat tiles: Saldo Kas, Saldo Bank, Total Piutang Siswa, Siswa Aktif |
| `PendapatanPengeluaranChart` | 12-month bar chart, revenue vs expense from journal lines |
| `TunggakanTeratasTable` | Top 10 students by outstanding (kelas + days overdue) |
| `AbsensiHariIniWidget` | Present % per grade today |
| `AgendaWidget` | Upcoming meetings/events/announcements (7 days) |
| `TugasSaya` | Contextual queues filtered by permission: draft batches awaiting issue, payroll awaiting approval, rapor awaiting approval |

## 3. `/portal` (wali murid) — read-only

Navigation: **Anak Saya** (children with kelas + wali kelas) · **Tagihan** (invoices + status +
kwitansi PDF of paid ones) · **Riwayat Pembayaran** · **Rapor** (published terms only; view + PDF) ·
**Absensi** (monthly H/S/I/A calendar per child) · **Jadwal Pelajaran** · **Pengumuman**.

- All queries scoped via `guardians.user_id = auth user` (policies in
  [09-auth-roles.md](09-auth-roles.md)).
- **No create/edit actions anywhere.** Raport visible only after `diterbitkan`; before that:
  "Rapor semester ini sedang dalam proses".
- Home dashboard: pinned pengumuman + outstanding tagihan summary per child.

## 4. PDF strategy

`barryvdh/laravel-dompdf` (^3). Print blades in `resources/views/pdf/`:

| Blade | Layout | Used for |
|---|---|---|
| `kwitansi.blade.php` | A5, 2 copies (asli/arsip), terbilang, per-invoice allocation | Payment receipts |
| `slip-gaji.blade.php` | A5 | Payslips |
| `rapor.blade.php` | A4, Kurikulum Merdeka layout (identity, per-subject nilai/predikat/deskripsi, ekstrakurikuler, prestasi, absensi, catatan, signatures) | Report cards, batch per class |
| `surat-keluar.blade.php` | A4 letterhead from SchoolSettings | Outgoing letters, berita acara |
| `laporan-keuangan.blade.php` | Generic A4 table wrapper (kop + period + fund) | NS, BB, LR, Neraca, Arus Kas, BOS |
| `daftar-tunggakan.blade.php` | A4 list per student/class | Arrears notices |

Download/print via dedicated routes with policy checks (e.g. `/portal/kwitansi/{payment}` only for
own children). Dompdf suffices at this layout complexity; if rapor pagination ever fights back, the
fallback is browser print CSS — not a new PDF engine.
