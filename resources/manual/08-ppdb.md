# PPDB (Penerimaan Peserta Didik Baru)

Menu: **Administrasi → PPDB** · Akses: operator TU (penuh), kepala sekolah (lihat).

Satu baris = satu calon siswa. Setiap pendaftaran mendapat **No. Pendaftaran** otomatis.

## Status Pendaftaran

```
Baru → Verifikasi → Diterima → Terdaftar
              ├── Cadangkan
              └── Ditolak
```

## Mendaftarkan Calon Siswa

1. Buka **PPDB**, klik **Tambah**.
2. Isi: **Tahun Ajaran**, **Nama Calon Siswa**, **Jenis Kelamin**, **Tanggal & Tempat Lahir**, **Agama**, **NIK**, **Asal TK/PAUD**, **Alamat**, **Nama Ayah**, **Nama Ibu**, **Telepon Orang Tua**, **Catatan** (opsional). Nama ayah, ibu, dan telepon wajib.
3. Klik **Buat**. Status awal **Baru**; No. Pendaftaran terbit otomatis.

## Alur Seleksi

Tombol aksi per baris (masing-masing dengan konfirmasi), hanya tampil sesuai status:

- **Verifikasi** (dari Baru) — menandai dokumen sudah diperiksa.
- **Terima** (dari Verifikasi) — calon diterima.
- **Cadangkan** (dari Verifikasi) — masuk daftar cadangan.
- **Tolak** (dari Verifikasi) — ditolak.
- **Daftarkan** (hanya dari Diterima) — jadikan siswa aktif, lihat di bawah.

## Daftarkan → Menjadi Siswa Aktif

Klik **Daftarkan** pada pendaftaran berstatus **Diterima**. Isi jendela:

- **Rombel Tujuan** (wajib) — hanya rombel **aktif** pada tahun ajaran pendaftaran.
- **Pindahan (mutasi masuk)** — nyalakan bila siswa pindahan; sistem mencatat pergerakan *mutasi masuk*.
- **Buat akun portal wali** — bila dinyalakan, wajib mengisi **Email Wali**; sistem membuat akun pengguna portal dan menautkannya ke baris wali tersebut (email untuk masuk portal wali murid).
- **Nama Wali / Telepon Wali** (opsional) — baris wali ketiga bila ada wali tambahan.

Klik tombol konfirmasi. **Sekali jalan dalam satu transaksi aman** sistem:

1. Membuat data **siswa** dengan **NIS otomatis** (berurutan).
2. Membuat **dua baris wali** (ayah & ibu) dari data pendaftaran, plus wali tambahan bila diisi; email wali utama terisi bila diisi.
3. Membuat **penempatan kelas (enrollment)** ke rombel tujuan berstatus aktif.
4. Membebankan **biaya default** sesuai tingkat kelas dan tahun ajaran (lihat bab Keuangan Siswa).
5. Membuat **pergerakan siswa** jenis mutasi masuk (bila pindahan).
6. Membuat **akun portal** wali (bila diminta) dan menautkannya.
7. Status pendaftaran menjadi **Terdaftar**; notifikasi menampilkan NIS siswa baru.

Bila ada kegagalan (mis. rombel tidak valid), seluruh proses dibatalkan dan tidak ada data tertinggal.

## Menghapus Pendaftaran

Pendaftaran yang salah input dapat dihapus per baris atau massal. Pendaftaran yang sudah **Terdaftar** jangan dihapus — hapus/mutakhirkan lewat data siswanya.
