# 08 — Operations: RKAS, Korespondensi, Rapat, Kegiatan, Pengumuman

## 1. RKAS (Rencana Kerja Anggaran Sekolah)

- `rkas_items`: planned amounts per **academic year × fund × expense account × activity name**
  ("Pengadaan ATK Semester 1" under 5-1400, fund BOS).
- **Realization is never cached** — computed live as Σ `journal_lines` matching
  (account, fund, academic-year periods). No drift is possible.
- Filament page: planned vs realized vs %, per fund and per account, monthly drill-down,
  red flags over 100%. Export PDF.
- RKAS is informational budgeting for this round — commitments/POs are out of scope.
- The `events.fund_id` + expense-form event link (below) gives activity-level realization too.

## 2. Surat masuk / surat keluar

### Surat masuk (`incoming_letters`)
- `agenda_no` from `document_sequences` (per year).
- Letter data: nomor asal, sender, subject, type (`undangan/edaran/permohonan/keputusan/lainnya`),
  received date, destination, **disposition** text, status `baru → diproses → selesai`,
  archived date.
- Scan attached via media library (`singleFile`).

### Surat keluar (`outgoing_letters`)
- `letter_no` from `document_sequences` — `SK/{yyyy}/{n}`, sequential per year.
- Draft → print (dompdf `surat-keluar` blade with school letterhead from `SchoolSettings` —
  kop surat: name, NPSN, address, logo) → `ditandatangani` (signed_by = kepala sekolah) → `terkirim`.

## 3. Rapat (meetings)

- `meetings`: title, date, time, location, organizer, agenda, **notulen** (rich text), status
  `terjadwal → terlaksana / dibatalkan`.
- `meeting_attendees`: employees and/or guest names, attended checkbox.
- Berita acara PDF export (reuses the letterhead blade + minutes).

## 4. Kegiatan (events)

- `events`: name, type, date range, location, description, **fund**, **budget_amount**, status
  `rencana → berlangsung → selesai / dibatalkan`.
- Realized amount = Σ journal lines whose entry references the event (the expense form offers an
  optional event link that posts to 5-1600 with the event's fund). `realized_amount` is a display
  cache recomputed on demand, never a source of truth.

## 5. Pengumuman (announcements)

- `announcements`: title, body, **audience** (`semua` / `guru` / `wali_murid`), scheduled
  `publish_at`, `expires_at`, pinned flag, status `draft` / `terbit`.
- Shown on the admin dashboard and the parent portal home; delivered as Laravel **database
  notifications** on publish (audience-filtered).

## 6. Kalender pendidikan

- `calendar_days`: one row per date per term — `effektif` / `libur` / `ujian` / `kegiatan` with a
  description. Seeded per academic year (national holidays + school-specific dates are data).
- Consumed by: attendance validation (no input on non-effective days — warning, not hard block),
  effective-day counts, and future schedule planning.
