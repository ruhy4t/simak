# Impor migrasi mutasi luar negeri melalui HeidiSQL

File: `allow_nullable_nisn_for_incoming_students.sql` (MySQL/MariaDB).

1. Masuk ke HeidiSQL, pilih database aplikasi dan buat cadangan melalui ekspor SQL.
2. Buka file SQL melalui menu File > Load SQL file.
3. Pastikan database aktif benar, lalu jalankan seluruh skrip dengan F9.
4. Hasil terakhir harus menampilkan **BERHASIL: NISN boleh kosong dan migrasi sudah tercatat**.
   Jika muncul error atau **PERIKSA**, simpan pesan untuk pemeriksaan lebih lanjut.

Tidak perlu menjalankan per bagian. Skrip aman diimpor ulang secara berurutan:
ALTER dilewati jika kolom sudah nullable, catatan migrasi tidak diduplikasi,
dan catatan baru hanya dibuat setelah struktur kolom dipastikan sesuai.
Tidak memerlukan stored procedure. Data murid dan NISN lama tetap tersimpan.

Setelah kode terbaru terpasang, muat ulang aplikasi. Centang **Murid berasal dari
Luar Negeri**, isi data murid, kosongkan NISN dan NPSN asal, lalu **Simpan Draft**.
Buka kembali draft untuk memeriksa data dan checklist.

Migrasi lokal sudah diterapkan. Impor ini untuk database server yang belum diperbarui.
Jika migrasi sudah dijalankan melalui Artisan, impor ulang melewati perubahan yang selesai.
