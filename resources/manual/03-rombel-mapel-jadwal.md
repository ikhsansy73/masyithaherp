# Rombel, Mata Pelajaran & Jadwal

Menu: **Akademik → Rombel / Mata Pelajaran / Jadwal** · Akses lihat & kelola: kepala sekolah (lihat), operator TU (penuh), guru & wali kelas (lihat).

## Rombel (Kelas)

Rombel = rombongan belajar, mis. **1A, 2B, 3A** untuk tahun ajaran tertentu.

### Membuat Rombel

1. Buka **Akademik → Rombel**, klik **Tambah**.
2. Isi kolom:
   - **Tahun Ajaran** (wajib) — pilih tahun ajaran rombel ini.
   - **Nama Rombel (mis. 3A)** (wajib, maks. 10 karakter) — harus unik dalam satu tahun ajaran.
   - **Tingkat** (wajib) — 1 sampai 6.
   - **Fase** (wajib) — A (kelas 1–2), B (kelas 3–4), C (kelas 5–6).
   - **Kapasitas** (opsional) — jumlah kursi maksimum.
   - **Wali Kelas** (opsional) — pilih pegawai; wali kelas dipakai untuk scoping rapor dan pengajuan rapor.
   - **Ruang** (opsional) — ruang kelas (dikelola di menu **Aset & Inventaris → Lokasi**).
   - **Aktif** — biarkan nyala untuk rombel berjalan.
3. Klik **Buat**.

### Mengubah & Menghapus Rombel

- **Edit** untuk mengubah wali kelas, ruang, kapasitas, atau status aktif.
- **Hapus** menghapus rombel beserta jadwal, pengampu mapel, dan penempatan siswa di rombel itu. Data siswa itu sendiri tidak terhapus — hanya penempatannya.

### Detail Rombel

Klik baris rombel untuk membuka halaman detail dengan tiga bagian:

- **Daftar Siswa** (baca saja): NIS, nama, tingkat, status penempatan (Aktif/Pindah/Keluar/Lulus), dengan filter status.
- **Pengampu Mata Pelajaran**: daftar mapel + guru pengampu + JP/minggu. Baris **tidak bisa ditambah dari UI** — baris pengampu disiapkan lewat persiapan data awal (seeding) oleh admin sistem. Edit dan hapus per baris tetap tersedia, mis. untuk mengganti guru pengampu.

### Pengampu Mapel — Cara Kerja

Baris di tabel pengampu (unik per rombel + mapel) adalah **sumber pilihan "Mata Pelajaran" di Jurnal Mengajar dan Penilaian**, sekaligus dasar penentuan rombel yang bisa dilihat seorang guru. Membuat jadwal **tidak** otomatis membuat baris pengampu; bila daftar pengampu suatu rombel masih kosong, minta admin sistem menyiapkannya sebelum guru mulai mengisi jurnal dan penilaian.

## Mata Pelajaran

Menu: **Akademik → Mata Pelajaran** (memakai izin rombel).

### Membuat Mapel

1. Klik **Tambah**, isi:
   - **Kode** (wajib, unik) — mis. MTK, BIN, PAI.
   - **Nama Mapel** (wajib).
   - **Kelompok** (wajib) — Kelompok A / Kelompok B.
   - **JP / Minggu** (default 2).
   - **Aktif** (default nyala).
2. Klik **Buat**.

### Menghapus Mapel

Mapel yang masih dipakai jadwal/pengampu **tidak dapat dihapus** (database menolak, tampil notifikasi galat). Nonaktifkan lewat toggle **Aktif** untuk menyembunyikannya dari pemilihan, atau hapus dulu semua jadwal & pengampu yang memakainya.

## Jadwal Pelajaran

### Membuat Jadwal

1. Buka **Akademik → Jadwal**, klik **Tambah**.
2. Pilih **Rombel**, **Mapel**, **Guru Pengampu** (hanya pegawai bertanda **Tenaga Pendidik**), **Semester**, **Hari** (Senin–Sabtu), **Jam Mulai**, **Jam Selesai** — semuanya wajib.
3. Klik **Buat**.

Aturan: satu rombel tidak boleh punya dua pelajaran pada **hari + jam mulai yang sama** (database menolak dengan galat).

### Mengubah & Menghapus Jadwal

Edit/Hapus per baris tersedia. Filter tersedia per **Rombel** dan **Hari**; kolom Semester disembunyikan secara default — gunakan kolom toggle untuk menampilkannya.

## Catatan tentang Pilihan Kelas

Di Jurnal Mengajar, Penilaian, Absensi, Kenaikan Kelas, dan Generate Rapor, pilihan **Rombel** hanya menampilkan rombel **aktif**, dan bagi guru/wali kelas hanya rombel **dalam cakupannya** (rombel yang diwalikan atau yang mapelnya dia ampu). Operator TU, bendahara, kepala sekolah melihat semua rombel.
