# Keuangan Siswa (Tagihan & Pembayaran)

Menu grup **Keuangan**: **Jenis Biaya**, **Struktur Biaya**, **Biaya Siswa**, **Potongan**, **Batch Tagihan**, **Tagihan**, **Pembayaran**, serta halaman **Laporan → Tunggakan** · Akses utama: bendahara; kepala sekolah lihat.

## Jenis Biaya

Definisi item biaya sekolah: **Kode** (unik, mis. SPP), **Nama**, **Kategori** (**Bulanan** / **Tahunan** / **Insidental** — hanya kategori Bulanan yang bisa dibuat lewat Generate Batch), **Akun Pendapatan** (akun 4-xxxx di akuntansi), toggle **Aktif**. Jenis biaya yang sudah dipakai tagihan tidak dapat dihapus — nonaktifkan saja.

## Struktur Biaya

Harga standar per **tahun ajaran × jenis biaya**, boleh diperinci per **tingkat** (kelas 1–6) — biaya tingkat mengalahkan biaya umum. Kolom: Tahun Ajaran (default tahun aktif), Jenis Biaya, Tingkat (opsional), **Bidang Dana** (akun pendapatan), **Nominal (Rp)**. Isi struktur sebelum membuat batch tagihan — ini sumber nominal tagihan.

## Biaya Siswa

Pembayaran khusus per siswa (selain struktur umum): pilih **Siswa**, **Tahun Ajaran**, **Jenis Biaya**, **Nominal**, **Jumlah Bulan** (1–12), **Mulai Bulan** — sistem memunculkan daftar tagihan per bulan sesuai rentang itu. Dipakai mis. biaya praktikum untuk siswa tertentu. Halaman ini juga tempat melihat seluruh tagihan seorang siswa.

## Potongan (Diskon)

Pengurangan biaya untuk siswa tertentu: **Siswa**, **Tahun Ajaran**, **Jenis Biaya**, **Keterangan**, **Jenis Potongan** (Persen % / Nominal Rp), **Nilai**, **Mulai Bulan**. Potongan aktif mengurangi tagihan siswa yang dibuat setelahnya; potongan tidak berlaku surut pada tagihan yang sudah terbit.

## Batch Tagihan

Membuat tagihan massal sekaligus:

1. Klik **Generate Batch** di halaman Batch Tagihan.
2. Isi: **Tahun Ajaran** (default tahun aktif), **Jenis Biaya** (hanya jenis kategori **Bulanan** yang aktif), **Bulan Tagihan** (default bulan depan), **Tingkat** (opsional, untuk membatasi per kelas).
3. Klik **Buat**. Sistem membuat satu tagihan per siswa yang belum punya tagihan jenis itu untuk bulan tersebut (tidak ada dobel) dan **memposting jurnal piutang** ke akuntansi. Notifikasi menampilkan jumlah tagihan dan total.

Status batch: **Draft** → **Diterbitkan** (tombol **Terbitkan** — tagihan resmi muncul di tagihan siswa) → dapat **Dibatalkan** (tombol **Batalkan**, wajib alasan; jurnal dibalik dan semua tagihan batch itu dibatalkan).

## Tagihan & Statusnya

| Status | Arti |
| --- | --- |
| Draft | Belum diterbitkan, belum jadi piutang |
| Terbit | Resmi; menunggu pembayaran |
| Dibayar Sebagian | Sebagian telah dibayar |
| Lunas | Terbayar penuh |
| Dibatalkan (Void) | Dibatalkan dengan alasan; jurnal dibalik |
| Cancelled | Batal sebelum terbit |

Tagihan yang sudah menerima pembayaran **tidak bisa dibatalkan** — sistem menolak dengan pesan *"Batalkan kwitansi terlebih dahulu."*

## Pembayaran & Kwitansi

1. Di halaman **Pembayaran** klik **Buat**: pilih **Siswa**, **Tanggal**, **Jumlah**, **Metode** (Tunai / Transfer / QRIS / E-Wallet / Lainnya), dan **Kas/Bank** tujuan.
2. Sistem menyalurkan pembayaran secara **FIFO** — tagihan tertua yang masih menunggu dibayar lebih dulu.
3. Setiap pembayaran terbit **No. Kwitansi** otomatis dan **memposting jurnal penerimaan kas** ke akuntansi.
4. Tombol **Cetak Kwitansi** membuka bukti pembayaran siap cetak.
5. **Batalkan** kwitansi (wajib alasan; izin khusus) membalik jurnalnya dan mengembalikan status tagihan terkait.

## Laporan Tunggakan

Halaman **Tunggakan** merangkum siswa dengan tagihan terbit/jatuh tempo, dengan **aging bucket**: 1–30, 31–60, 61–90, dan **> 90 hari** (disorot merah). Baris per siswa menampilkan kelas, sisa tunggakan per bucket, dan total. Orang tua melihat tagihan anaknya dari **portal wali murid**; dashboard sekolah juga menampilkan widget Total Tunggakan dan 10 tunggakan teratas.
