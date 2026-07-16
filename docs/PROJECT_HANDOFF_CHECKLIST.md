# Checklist Handoff SIMAK

Terakhir diperbarui: 16 Juli 2026

Dokumen ini menjadi acuan saat pengembangan dilanjutkan. Centang pekerjaan hanya setelah kode, konfigurasi production, dan smoke test deployment selesai.

## Sudah selesai

- [x] Mengaktifkan cache lintas-request dengan database pada deployment Vercel.
- [x] Menambahkan tabel `cache` dan `cache_locks` melalui migration dan SQL import manual.
- [x] Men-cache daftar tahun dashboard selama satu jam.
- [x] Membuat query tahun dashboard kompatibel dengan MySQL, PostgreSQL, dan SQLite.
- [x] Menambahkan regression test untuk cache settings, hari libur, dashboard, dan konfigurasi deployment.
- [x] Memisahkan Chart.js sebagai dynamic chunk agar tidak dimuat pada halaman tanpa chart.
- [x] Mengaktifkan header keamanan, secure cookie, session encryption, login rate limit, dan regenerasi session setelah login.
- [x] Memastikan dokumen privat diperiksa berdasarkan pemilik dan peran pengguna.
- [x] Memperbarui dependency PHP yang memiliki advisory keamanan; `composer audit` tidak lagi menemukan advisory.
- [x] Menghapus seluruh pematian verifikasi TLS pada integrasi Telegram.
- [x] Mewajibkan `TELEGRAM_WEBHOOK_SECRET` pada environment production.
- [x] Mengganti autentikasi cron menjadi `Authorization: Bearer` dengan perbandingan `hash_equals`.
- [x] Menghapus token cron dari path dan query string aplikasi.
- [x] Mempersempit pengecualian CSRF hanya ke `telegram/webhook`.
- [x] Mengurangi query N+1 saat mengambil penerima reminder Telegram.
- [x] Mengubah pengiriman dokumen B2 menjadi streaming agar tidak memuat seluruh file ke RAM.
- [x] Memindahkan reminder dari cron-job.org ke Vercel Cron pada pukul 23:00 UTC, diikuti queue worker pukul 00:00 UTC.

## Wajib sebelum deployment berikutnya

- [ ] Buat secret acak minimal 32 karakter untuk `CRON_SECRET` di environment Vercel/hosting.
- [ ] Buat secret Telegram yang hanya memakai `A-Z`, `a-z`, `0-9`, `_`, atau `-`, lalu simpan sebagai `TELEGRAM_WEBHOOK_SECRET`.
- [ ] Pastikan kedua Vercel Cron `/cron/reminder` dan `/cron/work` muncul setelah deployment dan menggunakan `CRON_SECRET` yang sama.
- [x] Daftarkan ulang webhook Telegram ke `/telegram/webhook` agar `secret_token` baru dikirim pada setiap webhook (dikonfirmasi berhasil 16 Juli 2026).
- [ ] Jalankan `composer install --no-dev --optimize-autoloader` pada build/deployment production.
- [ ] Jalankan `php artisan optimize` pada hosting persisten; untuk Vercel pastikan file cache diarahkan ke `/tmp`.
- [ ] Jalankan seluruh automated test, build frontend, dan smoke test login/dashboard/upload/download setelah deployment.
- [x] Verifikasi `composer audit --locked` dan `npm audit --omit=dev` tetap menghasilkan nol advisory (diverifikasi ulang 16 Juli 2026).

## Prioritas lanjutan

### Keamanan

- [x] Migrasikan inline script ke CSP nonce/hash, lalu hapus `'unsafe-inline'` dan `'unsafe-eval'` dari `script-src` CSP.
- [x] Hilangkan duplikasi route Telegram antara `web.php` dan `api.php`; URL webhook resmi: `/telegram/webhook`.
- [x] Tambahkan validasi kepemilikan, usia, ukuran, MIME, dan ekstensi upload sementara sebelum file dipindahkan menjadi dokumen permanen.
- [x] Tambahkan secret scanning dan dependency audit pada CI GitHub Actions.
- [x] Ubah lockout login menjadi rate limit per kombinasi username dan IP tanpa penguncian akun global.
- [ ] Rotasi berkala token Telegram, credential database, B2 key, dan cron secret.

### Performa dan operasional

- [x] Pindahkan reminder dan auto-cancel sepenuhnya dari middleware dashboard ke scheduled command.
- [x] Satukan logika reminder dan auto-cancel menjadi satu `DailyTaskService`.
- [x] Proses reminder dan auto-cancel dengan `chunkById` untuk data besar.
- [x] Tambahkan jadwal pembersihan cache database yang kedaluwarsa dan upload sementara yang terbengkalai.
- [x] Tambahkan observability untuk durasi request, query lambat, kegagalan queue, dan kegagalan Telegram.
- [ ] Pertimbangkan Redis saat trafik dan concurrency sudah melebihi kemampuan cache/queue database.

## Catatan kompatibilitas

- URL lama `/cron/reminder/{token}` sengaja tidak digunakan lagi karena token dapat tercatat di access log.
- Akun cron-job.org tidak lagi diperlukan; seluruh jadwal harian dikelola melalui `vercel.json`.
- Jadwal Vercel menggunakan UTC: reminder `23:00 UTC` (sekitar `06:00 WIB`) dan queue worker `00:00 UTC` (sekitar `07:00 WIB`).
- Endpoint cron sekarang menolak request tanpa Bearer token dengan status `401`.
- Webhook Telegram production menolak request jika `TELEGRAM_WEBHOOK_SECRET` belum dikonfigurasi.
- Upload sementara lama yang belum memakai namespace `temp_uploads/{user_id}/` harus diunggah ulang setelah deployment hardening upload.
- Perubahan CSP penuh belum dilakukan karena Alpine dan sejumlah inline script perlu dimigrasikan terlebih dahulu agar UI tidak rusak.
