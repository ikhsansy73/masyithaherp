# Siswa (Data Induk)

Menu: **Master → Siswa** · Akses: lihat — kepala sekolah, operator TU, bendahara, guru (cakupan rombelnya); kelola penuh — operator TU. Wali murid tidak membuka panel sekolah; mereka melihat data anak lewat portal (lihat bawah).

## Membuat Data Siswa

1. Buka **Siswa**, klik **Tambah**.
2. Isi kolom:

   | Kolom | Wajib | Ketentuan |
   | --- | --- | --- |
   | NIS | ✔ | Unik, maks. 20 karakter |
   | NISN | – | Unik, maks. 10 karakter (kosong bila belum ada) |
   | NIK | – | Unik, maks. 16 karakter |
   | Nama Lengkap | ✔ | Maks. 255 karakter |
   | Jenis Kelamin | ✔ | Laki-laki / Perempuan |
   | Tanggal & Tempat Lahir | ✔ / – | |
   | Agama | ✔ | |
   | Alamat | – | |
   | No. KK, No. Akta, Telepon | – | |
   | Status | ✔ | Aktif / Lulus / Keluar / Mutasi (default Aktif) |
   | Tanggal Masuk / Tanggal Keluar / Alasan Keluar | – | Tanggal keluar diisi saat siswa berhenti |
   | Foto | – | Gambar profil siswa |

3. Klik **Buat**.

NIS biasanya diberikan otomatis oleh proses **PPDB → Daftarkan**; pembuatan manual dipakai untuk data lama/mutasi masuk.

## Mengubah & Menghapus Siswa

- **Edit** mengubah semua kolom di atas, termasuk status (mis. saat siswa keluar: isi Status, Tanggal Keluar, dan Alasan Keluar).
- **Hapus** adalah **hapus lunak**: data siswa masih tersimpan dan bisa dipulihkan admin, tetapi hilang dari daftar. Sebaiknya gunakan perubahan **Status** (Keluar/Lulus/Mutasi) alih-alih menghapus, agar riwayat tagihan dan rapor tetap utuh.

## Halaman Detail Siswa

Klik baris siswa untuk membuka detail dengan tiga bagian:

- **Penempatan Kelas (Enrollments)** — **baca saja**: tahun ajaran, rombel, tingkat, status penempatan (Aktif/Pindah/Keluar/Lulus). Penempatan baru tercipta dari proses **PPDB → Daftarkan** atau **Kenaikan Kelas**, bukan dari layar ini.
- **Orang Tua/Wali (Guardians)** — bisa ditambah/diubah/dihapus: hubungan (Ayah/Ibu/Wali), nama, NIK, pekerjaan, pendidikan, telepon, email, tanda **Kontak Utama**. Wali yang ditautkan ke akun pengguna adalah yang bisa masuk portal wali murid.
- **Biaya Siswa (Student Fees)** — baca saja: daftar biaya yang dibebankan ke siswa ini beserta statusnya; dikelola dari modul Keuangan (bab Keuangan Siswa).

## Pergerakan Siswa (Audit)

Menu ini **rekaman otomatis** setiap perubahan penempatan siswa: jenis (naik kelas, pindah, keluar, masuk), rombel asal → rombel tujuan, tanggal, catatan, dan petugas yang mencatat. Datanya **baca saja** — tidak ada tombol buat/edit/hapus. Gunakan untuk melacak riwayat kelas seorang siswa atau audit mutasi.

## Portal Wali Murid

Wali murid **tidak membuka panel sekolah** — mereka masuk ke portal terpisah (alamat portal disediakan admin) memakai akun yang ditautkan ke baris wali. Di portal, wali murid melihat **data anak**, **tagihan**, dan **rapor** yang berstatus Diterbitkan. Penautan akun dilakukan admin pada proses PPDB → Daftarkan (akun portal dibuat otomatis) atau manual di menu Pengguna.
