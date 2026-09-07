# Migrasi mutasi masuk luar negeri melalui HeidiSQL

Gunakan skrip `allow_nullable_nisn_for_incoming_students.sql` pada database
MySQL/MariaDB aplikasi. Tidak perlu akses CLI atau menjalankan Artisan.

1. Masuk ke HeidiSQL dan pilih database aplikasi pada panel kiri.
2. Buat cadangan database melalui menu ekspor database sebagai SQL.
3. Buka tab Query, lalu buka atau salin isi skrip SQL di atas.
4. Jalankan bagian 1. Pastikan `database_aktif` adalah database aplikasi dan
   kolom `nisn` pada tabel `mutasi_masuk_siswa` ditemukan.
5. Jalankan bagian 2 (`ALTER TABLE`). Tunggu hingga berhasil. Jika ada error,
   berhenti dan simpan pesan error; jangan lanjut ke bagian 3.
6. Jalankan bagian 3 untuk mencatat migrasi Laravel. Jika sudah tercatat,
   skrip tidak menambahkan catatan duplikat.
7. Jalankan bagian 4. Hasil kolom `nisn` harus menunjukkan `Null = YES`, dan
   query `migrations` harus menampilkan satu baris untuk migrasi tersebut.
8. Setelah kode terbaru terpasang, muat ulang halaman aplikasi. Centang
   **Murid berasal dari Luar Negeri**, isi data murid, kosongkan NISN dan
   NPSN asal, lalu pilih **Simpan Draft**. Buka kembali draft untuk memastikan
   data dan status luar negeri tetap tersimpan. Uji pada akses Dinas dan
   Sekolah/Operator.

Perubahan ini hanya membuat kolom NISN boleh berisi NULL. NISN yang sudah ada
tetap tersimpan. Aplikasi tetap mewajibkan NISN untuk murid dalam negeri dan
memvalidasi 10 digit jika NISN diisi. Dokumen boleh dilengkapi kemudian selama
masih draft.

Migrasi lokal pada komputer pengembangan sudah diterapkan. Database server
perlu diperbarui sendiri melalui langkah di atas. Sebaiknya jalankan SQL
sebelum pengguna memakai fitur baru. Tidak perlu menjalankan SQL ini jika
migrasi yang sama sudah berhasil dijalankan melalui Artisan.
