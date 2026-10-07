# 06 — Students, Enrollment & Academics (Kurikulum Merdeka)

Module services: `App\Services\School\PpdbService`, `App\Services\School\StudentMovementService`,
`App\Services\Academics\ReportCardService`, `FinalScoreCalculator`.

## 1. Students & guardians

### Identity
- **NIS** — internal school number, required, unique (auto-generated from `document_sequences`).
- **NISN** — national 10-digit number, nullable but unique when present.
- **NIK** — 16-digit, nullable but unique when present.
- Religion, birth data, address, KK/akta numbers, photo (media library `singleFile`).
- `status`: `aktif`, `lulus`, `mutasi_keluar`, `keluar`, `cadangan` (PPDB waitlist before enrollment).

### Guardians (wali)
Denormalized per student (`guardians.student_id`): one `ayah`, one `ibu`, one `wali` row max each
(unique `(student_id, relationship)`).

- **No `families` table** — at ≤300 students, sibling dedup adds a join and an admin UI nobody will
  thank us for. The "copy guardian data to sibling" action covers the exception case.
- `guardians.user_id` (**plain index, not unique**) is the portal login link: one parent account may
  be guardian on several students' rows; children are resolved via that relation — no pivot needed.
- `is_primary_contact` determines who receives tunggakan notices.

## 2. PPDB (penerimaan siswa baru)

`ppdb_registrations` flow:

```
baru → verifikasi → diterima / cadangan / ditolak
diterima → (action: Daftarkan) → terdaftar
```

The **Daftarkan** action (one transaction) creates:
1. `students` row (NIS from sequence, status `aktif`),
2. three `guardians` rows from the registration data,
3. `student_enrollments` into the target rombel for the academic year,
4. `student_fees` via `StudentFeeService::assignDefaultFees()`,
5. a `student_movements` row (`mutasi_masuk` if transferring in, else plain entry),
6. optionally invites the parent email to create a portal account (welcome notification).

## 3. Classrooms (rombel), placement & promotion

- `classrooms` are **per academic year** — "Kelas 1A 2026/2027" is a different row from
  "Kelas 1A 2027/2028". `fase` derived from grade (1–2→A, 3–4→B, 5–6→C) and stored.
- `student_enrollments` unique per `(student, academic_year)`; end-of-year rollover closes the old row.
- **Promotion wizard** (end of academic year, wali kelas/operator, kepala_sekolah executes):
  per student a decision — `naik` (auto-target next grade's classroom of the new year) /
  `tinggal_kelas` / `lulus` (kelas 6) / `mutasi_keluar` / `keluar`. Executing:
  closes old enrollments, creates new-year enrollments, updates `students.status`, writes
  `student_movements` (single audit trail for all placement changes).
- New academic year setup: create year → wizard creates 6 rombel (1A–6B per school config) →
  assign wali kelas → run promotion.

## 4. Daily attendance

One row per student per day (SD model, not per-subject): `student_attendances` with `hadir/sakit/izin/alpa`.

- Input UI: pick class + date → grid of students, bulk-set "hadir", toggle exceptions.
- Unique `(student_id, date)`; late edits allowed only for the recording class's teacher/operator.
- Feeds: rapor attendance snapshot, dashboard widget (present % per grade), parent portal.

## 5. Curriculum: CP → TP (Kurikulum Merdeka)

- `learning_achievements` (**CP**, Capaian Pembelajaran): seeded per `subject × fase × elemen`
  from the official BSKAP CP documents (e.g. Matematika Fase A, elemen "Bilangan").
- `learning_objectives` (**TP**, Tujuan Pembelajaran): authored by teachers per semester under each
  CP, ordered by `sequence`. Assessments and teaching journals link to TPs.
- `subjects`: kelompok A/B, JP per week (kelompok A: PKN, BIN, BIG, MTK, IPA/SBdP-integrated per
  school config; kelompok B: PAI, BING, PJOK, SBDP…; actual list is data, editable).
- `class_subject_teachers` unique `(classroom_id, subject_id)` — in SD practice mostly the wali kelas.
- `schedules`: weekly grid per rombel per term.
- `teacher_learning_journals`: quick daily entry (date, TP covered, topic, method, notes) — evidence
  of pembelajaran and input for rapor descriptions.

## 6. Assessment & grades

- `assessments`: `formatif` / `sumatif` / `sumatif_akhir` × `pengetahuan` / `keterampilan`,
  optionally linked to a TP.
- `assessment_scores` per student: numeric `score` **or** rubric `predicate`
  (`BB/MB/BSH/SB`, used in Fase A) — both columns because Kurikulum Merdeka SD reports are
  descriptive while parents still expect numbers.
- **Final score formula**: `final = 40% × avg(formatif) + 60% × avg(sumatif)`
  (weights in `AcademicSettings`, overridable per subject later; `FinalScoreCalculator` unit-tested).
- **Predicate thresholds** (in settings): SB ≥ 90, BSH ≥ 80, MB ≥ 70, BB < 70.

## 7. Rapor (report cards)

Per student per term: one `report_cards` row + per-subject `report_card_subjects`
(nilai + predikat + deskripsi) + ekstrakurikuler + prestasi + attendance snapshot +
`catatan_wali_kelas` + height/weight.

### Generation
End-of-term action `ReportCardService::generate(term, classroom)`:
- computes finals via `FinalScoreCalculator`,
- **auto-drafts each subject description** from TP mastery — TPs whose mean ≥ threshold produce
  "Ananda sudah menunjukkan penguasaan yang baik dalam {ringkasan TP}…", below threshold produce
  "…masih perlu bimbingan dalam…" — inserted as **editable text**. Teachers edit wording; the math
  stays computed.

### Workflow
```
draft → diajukan (wali kelas submits) → disetujui (kepala sekolah) → diterbitkan
                    ↑                     │
                    └───── revisi ─────────┘  (with revision_note)
```
Enforced by status checks in `ReportCardService` + Filament actions gated by permissions
(see [09-auth-roles.md](09-auth-roles.md) matrix: only kepala_sekolah approves/publishes).

### Publishing & printing
- `diterbitkan` sets `published_at`; from that instant the **parent portal** shows the rapor
  (view + PDF). Before publication the portal shows only "Rapor semester ini sedang dalam proses".
- Bulk PDF: one A4 per student (`rapor` blade: identity block, per-subject nilai/predikat/deskripsi,
  ekstrakurikuler, prestasi, absensi, catatan wali kelas, signature blocks for kepala sekolah +
  wali kelas from settings), batch download per class.
