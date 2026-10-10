# Aset & Inventaris (ATK)

Menu grup **Aset & Inventaris**: **Aset**, **Kelompok Aset**, **Perawatan**, **Penghapusan** (bagian dari Aset), **Opname**, **ATK (Barang Habis Pakai)** · Akses: operator TU (catat, perawatan, opname, ATK), bendahara (penghapusan, penyusutan, pembelian aset).

## Kelompok Aset & Lokasi

- **Kelompok Aset** — kategori penyusutan (mis. meubelair, elektronik): nama, **masa manfaat default (bulan)**, **persentase penyusutan/tahun**. Kelompok yang masih memiliki aset tidak dapat dihapus.
- **Lokasi** — ruang/gedung tempat aset; juga dipakai sebagai pilihan **Ruang** saat membuat rombel.

## Mencatat Aset

Tombol **Catat Aset** di halaman Aset membuka jendela: **Nama Aset**, **Kelompok**, **Tanggal Perolehan**, **Biaya Perolehan (Rp)**, **Sumber Dana** (BOS/pendaftaran/dana lain), **Kondisi**, **Lokasi**, **Penanggung Jawab** (pegawai), **Merk/Tipe**, **No. Seri**, **Masa Manfaat (bulan)**, **Nilai Sisa (Rp)**. Bila aset dibeli tunai, isi **Cara Bayar** dan **Kas/Bank** — sistem otomatis memposting jurnal perolehan aset ke akuntansi.

Tombol **Duplikat** pada halaman detail aset menyalin data aset ke formulir baru — berguna saat membeli barang seragam sekaligus (mis. 30 kursi).

## Penyusutan

Penyusutan dihitung **per bulan oleh bendahara** dari halaman **Periode Akuntansi → Jalankan Penyusutan** (lihat bab Akuntansi). Aset yang sudah disusutkan untuk bulan itu dilewati otomatis, jadi menjalankan dua kali aman. Hasil: akumulasi penyusutan naik, nilai buku (perolehan − akumulasi) turun.

## Perawatan & Perbaikan

Catat biaya perawatan aset: aset, tanggal, jenis, biaya, keterangan. Biaya tercatat sebagai **beban perawatan** (akun 5-1800) ke akuntansi — bukan menambah nilai aset.

## Penghapusan Aset

Aset berstatus **aktif** dapat dihapuskan dengan salah satu cara:

- **Dijual** — isi hasil penjualan dan kas/bank penerima; sistem menghitung **nilai buku** (biaya − akumulasi penyusutan) dan memposting jurnal rugi/laba penghapusan.
- **Dihapuskan** — rusak total/tidak bernilai; tanpa hasil penjualan.
- **Hilang** — wajib merujuk **temuan opname** yang menyatakan aset tidak ditemukan.

Setelah dihapuskan, aset berstatus nonaktif dan keluar dari daftar aset berjalan, tetapi riwayatnya tetap terbaca.

## Opname (Stock Opname Aset)

1. **Buat Opname**: nama, tanggal, pelaksana (pegawai) — sistem menarik seluruh aset aktif sebagai daftar periksa.
2. **Periksa** setiap item: cocok / tidak ditemukan / kondisi berubah, dengan catatan.
3. **Finalisasi** hasil. Aset yang **tidak ditemukan** menjadi dasar penghapusan cara **Hilang**.

## ATK (Barang Habis Pakai)

Daftar item ATK dengan stok berjalan dan **stok minimum** (dasar widget "Stok Menipis" di dashboard).

- **Stok Masuk**: pembelian — jumlah, harga satuan, kas/bank; **harga rata-rata tertimbang** dihitung ulang otomatis dan jurnal pembelian terposting.
- **Stok Keluar**: pemakaian oleh unit/keperluan tertentu; stok berkurang dengan nilai rata-rata.
- Stok di bawah minimum ditandai di daftar dan dashboard supaya segera dibeli ulang.
