# SDM: Pegawai, Absensi Pegawai, Izin/Cuti & Payroll

Menu: **Kepegawaian → Pegawai / Absensi Pegawai / Izin & Cuti / Komponen Gaji / Payroll** · Akses: operator TU (pegawai, absensi, izin/cuti), bendahara (payroll, komponen gaji), kepala sekolah (menyetujui izin/cuti & payroll).

## Pegawai

Data induk seluruh karyawan: guru, tenaga kependidikan, satpam, dll. Kolom utama: **No. Pegawai** (unik), **Nama**, NIK, jenis kelamin, tempat/tanggal lahir, alamat, telepon, **Jabatan**, **Status Kepegawaian**, toggle **Tenaga Pendidik** (menentukan apakah pegawai bisa dipilih sebagai guru pengampu di jadwal), tanggal masuk/berhenti, status perkawinan, dan **Akun Login** untuk menautkan akun pengguna.

- **Edit/Hapus** per baris. Hapus bersifat **lunak** (data bisa dipulihkan admin).
- Pegawai bertanda Tenaga Pendidik muncul di pilihan guru (jadwal, jurnal, penilaian).

## Absensi Pegawai

Rekap kehadiran karyawan per hari: pilih pegawai + tanggal + status (Hadir/Sakit/Izin/Alpa/Dinas Luar). Menyimpan ulang tanggal yang sama **memperbarui** catatan. Rekap H/S/I/A inilah yang dipakai payroll untuk menghitung potongan ketidakhadiran.

## Izin & Cuti

1. **Buat pengajuan**: pegawai, jenis (izin/cuti/sakit dsb.), tanggal mulai & selesai (selesai ≥ mulai), alasan. Sistem menolak pengajuan yang **tanggalnya beririsan** dengan pengajuan lain pegawai itu.
2. Pengajuan masih bisa diedit selama **belum diproses**.
3. Kepala sekolah **Setujui** atau **Tolak**. Pengajuan yang **disetujui otomatis menulis baris absensi pegawai** untuk seluruh tanggalnya, jadi payroll ikut benar tanpa input ganda.

## Komponen Gaji

Definisi komponen pendapatan/potongan: **Kode** (mis. `GAJI_POKOK`, `BPJS`), Nama, **Jenis** (pendapatan/potongan), **Perhitungan** (nominal tetap atau persen dari GAJI_POKOK), **Nominal Default**. Komponen `GAJI_POKOK` wajib dikonfigurasi untuk tiap pegawai sebelum payroll bisa dihitung — BPJS dan komponen persen dihitung dari nilainya.

Konfigurasi per pegawai (nilai komponen milik pegawai) diatur dari halaman detail pegawai.

## Payroll (Penggajian Bulanan)

Satu **periode payroll** = satu bulan gaji seluruh pegawai. Alur status:

```
Draft → Dihitung → Disetujui → Dibayar
                              ↘ Dibatalkan (jurnal dibalik)
```

1. **Buat Payroll** — pilih **Tahun** dan **Bulan**; sistem membuat periode berstatus Draft berisi seluruh pegawai aktif.
2. Buka periode, klik **Hitung** — sistem menghitung tiap pegawai: gaji pokok + tunjangan − potongan ketidakhadiran (dari absensi) − BPJS − potongan lain = **neto**. Hasil tampil sebagai slip per pegawai.
3. Koreksi bila perlu lewat **Entri Manual** per pegawai (catatan tercatat di slip).
4. **Setujui** (kepala sekolah) — periode terkunci; jurnal **akrual beban gaji** terposting ke akuntansi.
5. **Bayar** — pilih **Kas/Bank** dan **Tanggal Pembayaran**; jurnal **pembayaran gaji** terposting dan slip menjadi resmi.
6. **Slip Gaji** per pegawai dapat dicetak PDF, tetapi hanya setelah periode **Disetujui/Dibayar** (draft tidak punya slip resmi).
7. **Batalkan** — membalik jurnal-jurnal yang sudah terposting; gunakan bila payroll salah bulan/komponen.

Terjadi galat umum: *"Pegawai … belum memiliki komponen GAJI_POKOK"* — lengkapi konfigurasi komponen pegawai itu lalu Hitung ulang.
