# Penilaian & Jurnal Mengajar

Menu: **Akademik → Penilaian**, **Akademik → Input Nilai**, **Akademik → Jurnal Mengajar** · Akses: guru, wali kelas, operator TU, kepala sekolah — dengan cakupan baris (lihat bawah).

## Penilaian (Daftar Asesmen)

Satu baris **Penilaian** = satu kegiatan penilaian untuk satu rombel × mata pelajaran, mis. "Ulangan Harian 1 Bilangan" jenis Formatif.

### Membuat Penilaian

Klik **Buat** di kanan atas, isi:

- **Rombel** (wajib) — hanya rombel dalam cakupan Anda.
- **Mata Pelajaran** (wajib) — pilihan mengikuti daftar **pengampu mapel** rombel terpilih.
- **Guru** (wajib) — daftar pegawai; otomatis terisi nama Anda bila Anda guru.
- **Semester** (wajib) — semester dari tahun ajaran aktif.
- **Nama Penilaian** (wajib) — mis. "UH 1 Penjumlahan".
- **Jenis** (wajib):
  - **Formatif** — penilaian proses harian.
  - **Sumatif** — ulangan/ujian per bab.
  - **Sumatif Akhir** — nilai akhir semester; menjadi bahan perhitungan rapor.
- **Aspek** (wajib) — **Pengetahuan** atau **Keterampilan**.
- **Tanggal** (wajib).
- **Nilai Maksimal** (wajib) — mis. 100. Nilai siswa tidak boleh melebihinya.
- **TP yang Diases** (opsional) — pilihan TP dari Kurikulum yang cocok dengan rombel + mapel; dipakai untuk perhitungan penguasaan TP di rapor.
- **Keterangan** (opsional).

Klik **Buat**. Setelah penilaian dibuat, isi nilainya lewat menu **Input Nilai** (lihat bawah).

### Mengubah & Menghapus Penilaian

- **Edit** mengubah semua kolom di atas.
- **Hapus** menghapus penilaian **beserta seluruh nilai siswa di dalamnya** — baca konfirmasi sebelum menyetujui.
- Penilaian yang sudah memiliki nilai tetap bisa diedit; mengubah Nilai Maksimal berlaku untuk penyimpanan nilai berikutnya.

### Cakupan Baris

- **Guru** dan **wali kelas** hanya melihat/mengelola penilaian untuk rombel yang mereka ampu/walikan.
- **Operator TU** dan **kepala sekolah** melihat semua penilaian.

## Input Nilai (Grid Kelas)

Menu khusus untuk mengisi nilai satu kelas sekaligus:

1. Pilih **Rombel** → **Semester** → **Penilaian** (daftar hanya penilaian rombel + semester terpilih, sesuai cakupan Anda).
2. Daftar siswa aktif rombel itu muncul dalam grid.
3. Untuk setiap siswa isi **salah satu**:
   - **Nilai** angka (0 s.d. Nilai Maksimal), atau
   - **Predikat** rubrik: **SB** (Sangat Baik), **BSH** (Baik Sekali), **MB** (Cukup Baik), **BB** (Perlu Bimbingan).
   - Mengisi keduanya ditolak: *"Isi salah satu saja: nilai angka atau predikat."* Mengosongkan keduanya menghapus nilai siswa itu.
4. Klik **Simpan**. Notifikasi menampilkan jumlah siswa yang tercatat.

Nilai yang sudah tersimpan dimuat kembali saat grid dibuka ulang, jadi mengisi bertahap aman. Menyimpan hanya bisa oleh pengguna dengan izin membuat penilaian.

## Jurnal Mengajar

Catatan pembelajaran harian per guru: materi apa, TP mana yang dibahas, metode apa.

### Membuat Jurnal

Klik **Buat**, isi:

- **Rombel** (wajib), **Mata Pelajaran** (wajib — dari daftar pengampu rombel), **Guru** (wajib), **Tanggal** (wajib).
- **TP yang Dibahas** (opsional) — dari Kurikulum.
- **Materi** (wajib) — ringkasan materi yang diajarkan.
- **Metode** (opsional) — mis. diskusi, drill.

Klik **Buat**. Edit/Hapus per baris tersedia; guru hanya melihat jurnal miliknya, sedangkan operator TU/kepala sekolah melihat semua (filter per rombel, mapel, dan guru tersedia).

## Hubungan dengan Modul Lain

- **Kurikulum**: pilihan TP di Penilaian dan Jurnal berasal dari CP/TP modul Kurikulum.
- **Rapor**: nilai Sumatif Akhir dirangkum menjadi nilai akhir mapel; penguasaan TP (rata-rata ≥ 75 dianggap tuntas) dan predikat rubrik menjadi deskripsi otomatis rapor.
