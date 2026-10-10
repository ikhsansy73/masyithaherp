# Akuntansi (Buku Besar Sekolah)

Menu grup **Akuntansi**: **Akun (COA)**, **Jurnal Umum**, **Kas & Bank**, **Periode Akuntansi**, dan lima laporan: **Neraca**, **Laba Rugi**, **Buku Besar**, **Neraca Saldo**, **Arus Kas** · Akses utama: bendahara; kepala sekolah lihat laporan; hanya super admin yang dapat **Buka Kembali** periode tertutup.

Setiap transaksi keuangan dari modul lain (tagihan, pembayaran, payroll, aset) otomatis memposting jurnal ke sini — modul ini untuk jurnal manual dan pelaporan.

## Akun (COA — Chart of Accounts)

Daftar akun baku sudah tersedia (kode 1-xxxx aset, 2-xxxx liabilitas, 3-xxxx ekuitas/dana, 4-xxxx pendapatan, 5-xxxx beban). Tombol **Buat** hanya untuk akun **beban baru**: kode wajib berformat `5-xxxx` (pesan sistem: "Kode akun baru harus berformat 5-xxxx"). Kolom lain: Nama Akun, Kelompok Akun (Beban), Saldo Normal, Kategori Arus Kas, Dana Bawaan, Aktif. Akun yang sudah punya jurnal tidak dapat dihapus; nonaktifkan untuk menyembunyikan dari pemilihan.

## Jurnal Umum

Membuat jurnal manual:

1. Klik **Tambah**: isi **Tanggal** (tidak boleh masa depan) dan **Keterangan**.
2. Di **Baris Jurnal**, tambahkan baris (minimal 2): pilih **Akun** dan **Dana**, isi **Debit** atau **Kredit** (satu saja per baris, tidak boleh negatif), memo opsional.
3. Sistem menolak menyimpan bila **total debit ≠ total kredit** ("Total debit harus sama dengan total kredit"), bila baris mengisi debit dan kredit sekaligus, atau bila periode akuntansi pada tanggal itu sudah ditutup.
4. Klik **Buat** — jurnal terposting dengan nomor otomatis.

**Batalkan** jurnal terposting (wajib alasan): sistem membuat **jurnal balikan otomatis** (cermin debit↔kredit), jadi saldo akun kembali benar tanpa menghapus riwayat.

## Kas & Bank

Daftar rekening kas/bank fisik sekolah (nama, nomor rekening, saldo berjalan, akun GL terkait). Dibutuhkan saat mencatat pembayaran siswa dan payroll — setiap uang masuk/keluar lewat satu kas/bank ini.

## Periode Akuntansi

Dua belas periode bulanan otomatis terbentuk saat tahun ajaran dibuat (Juli–Juni), status **Terbuka**.

- **Tutup Buku** (periode terbuka): membekukan periode — jurnal baru pada bulan itu ditolak. Konfirmasi dulu sebelum menjalankan.
- **Buka Kembali** (periode tertutup): hanya super admin; periode kembali terbuka untuk koreksi.
- **Jalankan Penyusutan** (dengan izin aset): menghitung penyusutan aset bulan periode itu; aset yang sudah disusutkan pada periode tersebut dilewati. (Lihat bab Aset & Inventaris.)

## Laporan Keuangan

| Laporan | Isi |
| --- | --- |
| **Neraca** | Posisi keuangan: aset = liabilitas + ekuitas/dana, per tanggal |
| **Laba Rugi** | Pendapatan vs beban per rentang periode |
| **Buku Besar** | Mutasi per akun per baris jurnal |
| **Neraca Saldo** | Saldo seluruh akun (debit/kredit) per tanggal |
| **Arus Kas** | Penerimaan & pengeluaran per kategori arus kas |

Semua laporan punya filter tanggal/periode; angka bergerak langsung saat jurnal baru terposting.
