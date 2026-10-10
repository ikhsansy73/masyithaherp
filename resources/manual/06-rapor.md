# Rapor (Rapor Digital)

Menu: **Akademik → Rapor** · Akses: kepala sekolah (penuh: setujui, minta revisi, terbitkan), wali kelas (mengajukan rapor kelasnya), guru (membuat/mengubah draft, cetak PDF), wali murid (melihat rapor anak yang sudah **Diterbitkan** lewat portal).

Rapor dibuat per **siswa × semester**. Isinya dirangkai otomatis dari nilai Penilaian, jadi pastikan nilai sudah lengkap sebelum membuat rapor.

## Alur Status Rapor

```
Draft → Diajukan → Disetujui → Diterbitkan
           ↓
      Perlu Revisi → (diajukan ulang)
```

| Status | Arti | Siapa yang bertindak |
| --- | --- | --- |
| Draft | Baru dibuat, masih bisa diubah | Wali kelas/guru |
| Diajukan | Dikirim ke kepala sekolah untuk diperiksa | Wali kelas (tombol **Ajukan**) |
| Perlu Revisi | Kepala sekolah meminta perbaikan (dengan catatan) | Kepala sekolah (tombol **Minta Revisi**) |
| Disetujui | Lolos pemeriksaan, menunggu penerbitan | Kepala sekolah (tombol **Setujui**) |
| Diterbitkan | Resmi; wali murid bisa melihat & mencetak | Kepala sekolah (tombol **Terbitkan**) |

## Membuat Rapor (Generate)

Tombol **Generate Rapor** di kanan atas daftar rapor:

1. Pilih **Rombel** dan **Semester**.
2. Klik **Buat**. Sistem membuat **satu draft rapor per siswa aktif** di rombel itu dan menampilkan jumlah yang dibuat.
3. Draft berisi nilai akhir per mapel dan deskripsi otomatis (lihat "Cara Perhitungan").
4. Raport siswa yang sudah punya rapor untuk rombel × semester itu tidak dibuat ulang (tidak ada duplikat).

## Mengubah Draft

Rapor berstatus **Draft** atau **Perlu Revisi** dapat diedit oleh pengguna dengan izin input rapor (guru/wali kelas): deskripsi per mata pelajaran dan catatan dapat disesuaikan. Setelah **Diajukan**, isi rapor terkunci hingga kembali ke status Perlu Revisi.

## Alur Kerja per Tombol

- **Ajukan** (wali kelas; status Draft/Perlu Revisi) — mengirim rapor ke kepala sekolah. Bisa massal: centang beberapa rapor lalu Ajukan.
- **Minta Revisi** (kepala sekolah; status Diajukan) — wajib mengisi **Catatan Revisi**; status kembali ke Perlu Revisi dan catatan tampil di halaman detail rapor.
- **Setujui** (kepala sekolah; status Diajukan).
- **Terbitkan** (kepala sekolah; status Disetujui) — rapor resmi dan langsung terlihat oleh wali murid di portal.
- **Cetak PDF** (per rapor) — membuka berkas PDF A4 siap cetak. Rapor hanya bisa dicetak setelah **Diterbitkan** (selain oleh pengguna sekolah sesuai izin).
- **Cetak Per Kelas** — memilih rombel + semester lalu mencetak seluruh rapor kelas itu sekaligus dalam satu berkas PDF.

## Cara Perhitungan Isi Rapor

- **Nilai akhir mapel** = 40% × rata-rata nilai **Formatif** + 60% × rata-rata nilai **Sumatif** (bobot dapat diubah admin di pengaturan akademik). Nilai Sumatif Akhir masuk kelompok sumatif.
- **Predikat rubrik** (untuk input berupa SB/BSH/MB/BB, umumnya Fase A): SB ≥ 90, BSH ≥ 80, MB ≥ 70, sisanya BB (ambang dapat diubah).
- **Deskripsi otomatis per mapel** dirangkai dari CP/TP: TP yang rata-rata nilainya **≥ 75** dianggap dikuasai dan masuk kalimat "Menunjukkan penguasaan baik pada: …". Guru boleh menyunting deskripsi pada draft.
- **Absensi** semester tercetak di rapor (jumlah sakit/izin/alpa).

## Pengaturan Angka Rapor

Nilai bobot dan ambang di atas adalah **pengaturan sistem** (tabel settings grup `academic`), diubah oleh super admin lewat database/artisan — bukan dari layar. Koordinasikan perubahan sebelum tahun ajaran berjalan agar angka rapor konsisten.
