# Pengenalan & Dasar Penggunaan

Selamat datang di **Sistem Informasi SD Masyithah**. Buku panduan ini menjelaskan seluruh modul sistem: apa fungsinya, siapa yang boleh menggunakannya, dan cara membuat, mengubah, serta menghapus data di setiap modul.

## Masuk ke Sistem

1. Buka alamat sistem di peramban (disediakan oleh admin sekolah).
2. Masukkan **Email** dan **Kata Sandi** Anda.
3. Klik tombol **Masuk**.
4. Pengguna yang berstatus nonaktif tidak dapat masuk ke sistem.

Lupa kata sandi? Hubungi admin sekolah (super admin) untuk mengatur ulang kata sandi Anda melalui menu **Pengguna**.

## Beranda (Dashboard)

Setelah masuk, Anda dibawa ke halaman Beranda yang menampilkan ringkasan kondisi sekolah sesuai peran Anda:

- **Siswa Aktif** — jumlah siswa berstatus aktif (tampil untuk pengguna dengan izin melihat data siswa).
- **Total Tunggakan** — akumulasi tunggakan seluruh siswa, berwarna merah jika ada tunggakan (izin laporan tunggakan).
- **Stok Menipis** — jumlah item ATK yang stoknya berada di bawah minimum (izin aset & inventaris).
- **Tunggakan Teratas** — tabel 10 siswa dengan tunggakan terbesar, lengkap dengan kelas, jumlah, dan keterlambatan dalam hari (merah tebal bila lebih dari 90 hari).
- **Stok ATK Menipis** — tabel hingga 8 item di bawah stok minimum, diurut dari stok paling sedikit.

Widget yang tampil menyesuaikan otomatis dengan peran Anda; pengguna dengan peran terbatas mungkin hanya melihat sebagian widget.

## Peran Pengguna

Sistem memiliki 7 peran. Satu pengguna bisa memegang lebih dari satu peran (mis. **guru** sekaligus **wali kelas**).

| Peran | Tanggung jawab utama |
| --- | --- |
| Super Admin | Pemilik sistem: mengelola pengguna, peran/izin, profil sekolah, membuka kembali periode akuntansi yang sudah ditutup |
| Kepala Sekolah | Pengawas seluruh data: melihat semua modul, menyetujui rapor, menyetujui izin/cuti, menyetujui & menerbitkan payroll |
| Bendahara | Keuangan penuh: tagihan, pembayaran, akuntansi, payroll, penyusutan, penghapusan aset, ATK |
| Operator TU | Administrasi: siswa, PPDB, rombel, jadwal, pegawai, absensi pegawai, izin/cuti, aset & ATK |
| Guru | Kurikulum (lihat), jurnal mengajar, penilaian, input nilai, absensi siswa, cetak rapor |
| Wali Kelas | Semua akses guru + mengajukan rapor kelasnya |
| Wali Murid | Portal orang tua: melihat data anak, tagihan, dan rapor anak yang sudah diterbitkan |

Menu yang tampil di bilah samping menyesuaikan peran: modul tanpa izin **tidak muncul** di menu.

## Pola Umum Layar Data

Hampir semua modul memakai pola layar yang sama, jadi cukup dipelajari sekali:

- **Tabel data**: setiap baris adalah satu data. Kolom bisa diklik untuk mengurutkan. Banyak tabel punya kolom pencarian di kanan atas dan tombol **Filter** untuk menyaring (mis. per status, per kelas).
- **Tombol aksi**:
  - **Buat / Tambah** di kanan atas — membuka formulir untuk data baru.
  - **Edit** (ikon pensil) di setiap baris — mengubah data.
  - **Lihat** (ikon mata) — halaman detail, sering berisi tabel turunan.
  - **Hapus** (ikon tempat sampah) — menghapus data, biasanya dengan konfirmasi.
- **Aksi khusus** berwarna sesuai fungsinya (hijau sukses, kuning menunggu, merah bahaya).
- **Notifikasi**: setiap aksi menghasilkan notifikasi di pojok kanan atas. Notifikasi hijau = berhasil, merah = gagal beserta alasannya.
- **Penghapusan bersama** (bulk): centang beberapa baris lalu pilih Hapus yang massal.
- **Banyak modul membuka formulir dalam jendela modal**; isi kolom lalu klik **Buat**/**Simpan**.

### Aturan Menghapus Data

Sebelum menghapus, perhatikan aturan berikut (aturan ini mencegah data keuangan/akademik rusak):

- Data yang sudah **terkunci** oleh proses (mis. jurnal terposting, payroll disetujui) tidak dapat diedit/dihapus — gunakan tombol **Batalkan** yang menghasilkan jurnal balikan.
- Beberapa data **tidak bisa dihapus** bila masih dirujuk: mis. jenis biaya yang sudah dipakai tagihan, akun yang sudah punya jurnal, kelompok aset yang masih memiliki aset.
- Menghapus induk dapat **menghapus ikut turunannya** (mis. menghapus rombel menghapus jadwal & pengampu mapelnya; menghapus penilaian menghapus semua nilainya). Sistem memperingatkan melalui konfirmasi, jadi baca sebelum menyetujui.
- Hanya data **Siswa** dan **Pegawai** yang dihapus secara lunak (masih bisa dipulihkan admin); modul lain dihapus permanen.

## Membaca Panduan Ini

Gunakan **Daftar Bab** di samping kiri untuk melompat antar bab. Kotak **Cari di panduan…** menelusuri seluruh isi buku panduan — ketik kata kunci (mis. "tunggakan", "rapor", "penyusutan") lalu klik hasilnya untuk langsung menuju bagian yang dimaksud.
