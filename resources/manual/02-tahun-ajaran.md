# Tahun Ajaran

Menu: **Akademik → Tahun Ajaran** · Ikon kalender · Akses: lihat — kepala sekolah, bendahara, guru, wali kelas; kelola penuh — operator TU.

Tahun ajaran adalah induk dari hampir semua data: rombel, semester, periode akuntansi, tagihan, dan rapor. Satu tahun ajaran berjalan dari **1 Juli sampai 30 Juni**.

## Membuat Tahun Ajaran

1. Buka **Akademik → Tahun Ajaran**, klik **Tambah**.
2. Isi **Tahun Ajaran** dengan format `2026/2027` (format wajib; tahun akhir harus satu tahun setelah tahun awal).
3. Aktifkan **Jadikan Tahun Ajaran Default** bila tahun ajaran ini akan langsung dipakai untuk semua transaksi harian. Hanya ada satu tahun ajaran default; mengaktifkannya memindahkan default dari tahun ajaran lain.
4. Klik **Buat**. Status tahun ajaran baru:
   - **Direncanakan** — bila belum dijadikan default (tahun pertama di sistem otomatis **Aktif**).

**Efek otomatis saat dibuat** (tidak perlu diinput manual):

- Dua semester dibuat: **Semester 1 — Ganjil** (1 Jul–31 Des) dan **Semester 2 — Genap** (1 Jan–30 Jun).
- Dua belas **periode akuntansi** bulanan (2026-07 s.d. 2027-06) dibuat berstatus **Terbuka**.

## Mengubah Tahun Ajaran

- Klik ikon **Edit**. Satu-satunya kolom yang bisa diubah adalah toggle **Jadikan Tahun Ajaran Default**; nama tahun ajaran **tidak dapat diubah** setelah dibuat.
- Toggle default tidak dapat dimatikan pada tahun ajaran yang sedang default — untuk memindahkan default, aktifkan toggle pada tahun ajaran lain.

## Menghapus Tahun Ajaran

Menghapus tahun ajaran akan menghapus **ikut seluruh turunannya**: semester, periode akuntansi, rombel, serta jadwal/pengampu/keterkaitan tagihan dan PPDB yang menggantung pada tahun tersebut. Pastikan tahun ajaran benar-benar salah dibuat sebelum menghapus. Gunakan konfirmasi yang muncul untuk memeriksa kembali.

## Status Tahun Ajaran

| Status | Arti |
| --- | --- |
| Direncanakan | Sudah dibuat, belum berjalan |
| Aktif | Sedang berjalan (tahun default otomatis aktif) |
| Ditutup | Sudah selesai |

## Alur Tahunan yang Disarankan

1. Akhir tahun ajaran (± Juni): buat tahun ajaran baru, jadikan default saat tahun lama selesai.
2. Rombel tahun baru dibuat dari proses **Kenaikan Kelas** (lihat bab Absensi & Kenaikan Kelas) atau manual di menu **Rombel**.
3. Struktur biaya tahun baru diisi di menu **Struktur Biaya** sebelum batch tagihan dibuat.
