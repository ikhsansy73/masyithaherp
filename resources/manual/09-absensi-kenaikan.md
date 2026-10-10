# Absensi Siswa & Kenaikan Kelas

## Absensi Siswa

Menu: **Akademik → Absensi Siswa** · Akses: wali kelas & guru (rombelnya), operator TU (semua rombel).

Mencatat kehadiran harian satu rombel:

1. Pilih **Rombel** dan **Tanggal**.
2. Daftar siswa aktif rombel itu muncul, masing-masing dengan pilihan status: **Hadir**, **Sakit**, **Izin**, **Alpa**.
3. Untuk mempercepat, klik **Tandai semua Hadir** lalu ubah siswa yang berbeda satu per satu.
4. Klik **Simpan**. Menyimpan ulang pada tanggal yang sama **memperbarui** catatan (bukan menduplikasi).

Rekap absensi per semester tercetak di **rapor** (jumlah sakit/izin/alpa), jadi pastikan absensi harian terisi sebelum rapor diterbitkan.

## Kenaikan Kelas

Menu: **Akademik → Kenaikan Kelas** · Akses: operator TU, kepala sekolah.

Proses akhir tahun ajaran untuk memindahkan seluruh isi satu rombel ke tahun ajaran baru:

1. Pilih **rombel asal** (tahun ajaran yang akan selesai) dan **tahun ajaran target** — target harus setelah tahun ajaran rombel asal.
2. Sistem menampilkan seluruh siswa aktif rombel itu; tentukan **keputusan** per siswa:

   | Keputusan | Efek |
   | --- | --- |
   | **Naik** | Masuk rombel setingkat +1 di tahun target (mis. 3A → 4A). Rombel tujuan **dibuat otomatis** bila belum ada. Tidak berlaku untuk kelas 6. |
   | **Tinggal kelas** | Masuk rombel setingkat yang sama di tahun target (mis. 3A → 3A tahun baru). |
   | **Lulus** | Hanya untuk kelas 6; status penempatan menjadi Lulus, tanpa penempatan baru. |
   | **Mutasi keluar / Keluar** | Status penempatan ditutup; tercatat di Pergerakan Siswa. |

3. Tentukan **tanggal pergerakan** (dan catatan bila perlu), lalu jalankan.
4. Sistem memproses sekaligus: penempatan lama ditutup, penempatan baru dibuat, **Pergerakan Siswa** tercatat otomatis untuk tiap siswa.

Aturan yang dijaga sistem:

- Siswa kelas 6 tidak bisa "Naik" — gunakan **Lulus**; sebaliknya hanya kelas 6 yang bisa diluluskan.
- Siswa yang sudah punya penempatan di tahun target ditolak (mencegah dobel).
- Seluruh proses dalam satu transaksi — bila satu siswa bermasalah, tidak ada yang setengah terproses.

Untuk siswa yang keluar di tengah tahun (bukan saat kenaikan), gunakan edit data siswa (status + tanggal keluar) — pergerakannya juga tercatat.
