# Kurikulum (CP/TP)

Menu: **Akademik → Kurikulum (CP/TP)** · Ikon topi akademik · Akses: lihat — kepala sekolah, guru, wali kelas; kelola penuh — operator TU (kecuali kebijakan sekolah menentukan lain).

Modul ini menyimpan dua hal yang saling terkait:

- **CP (Capaian Pembelajaran)** — kompetensi akhir yang harus dicapai per mata pelajaran per fase.
- **TP (Tujuan Pembelajaran)** — rincian CP menjadi target kecil per semester; inilah yang dinilai di modul Penilaian (penguasaan TP).

## Data Awal

Sistem sudah terisi **CP resmi** dari dokumen Kurikulum Merdeka (Keputusan Kepala BSKAP Nomor 032/H/KR/2022), diringkas per mapel dan fase A/B/C. CP bawaan biasanya tidak perlu diubah; tugas sekolah adalah menambah **TP** di bawah CP tersebut.

## Membuat CP

1. Buka **Akademik → Kurikulum (CP/TP)**, klik **Tambah**.
2. Isi kolom:
   - **Mata Pelajaran** (wajib) — pilih mapel dari menu Mata Pelajaran.
   - **Fase** (wajib) — A (kelas 1–2), B (kelas 3–4), atau C (kelas 5–6).
   - **Elemen** (wajib) — mis. "Bilangan", "Membaca & Menulis".
   - **Kode CP** (wajib, maks. 20 karakter) — mis. `PAI-A-1`.
   - **Deskripsi CP** (wajib) — bunyi capaian pembelajaran.
   - **Aktif** — default nyala; CP nonaktif disembunyikan dari pemilihan saat penilaian.
3. Klik **Buat**.

## Mengubah & Menghapus CP

- Klik ikon **Edit** di baris CP untuk mengubah semua kolom di atas.
- Klik ikon **Hapus** (atau centang beberapa baris → hapus massal). Menghapus CP **ikut menghapus semua TP di bawahnya** — periksa konfirmasi sebelum menyetujui.

## Filter & Pencarian

Tabel CP dapat disaring per **Mapel** dan per **Fase**; kolom Mapel dan Elemen dapat dicari.

## Mengelola TP

TP dikelola dari halaman detail CP:

1. Klik nama/baris CP untuk membuka halaman detail.
2. Di bagian bawah terdapat tabel **Tujuan Pembelajaran (TP)** milik CP itu.
3. Klik **Buat** untuk menambah TP:
   - **Kode TP** (wajib, maks. 30 karakter) — harus **unik di dalam CP ini**; bila duplikat, muncul pesan "Kode TP … sudah dipakai di CP ini."
   - **Deskripsi TP** (wajib) — bunyi tujuan pembelajaran yang terbaca di rapor.
   - **Semester** (wajib) — Semester 1 atau 2.
   - **Urutan** (wajib, angka bulat ≥ 1) — mengatur urutan tampil; tabel diurutkan per semester lalu urutan.
4. Klik **Buat**. Edit dan hapus per baris TP tersedia di tabel yang sama.

## Hubungan dengan Modul Lain

- **Penilaian / Input Nilai**: guru memilih TP saat menilai; penguasaan TP (rata-rata nilai ≥ 75 dianggap tuntas) menjadi bahan deskripsi rapor otomatis.
- **Rapor**: deskripsi rapor per mata pelajaran dirangkai dari CP/TP dan predikat nilai akhir.
