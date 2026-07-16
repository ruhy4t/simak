# Operasional Production SIMAK

Dokumen ini adalah prosedur aman untuk deployment, verifikasi secret, rotasi credential, dan keputusan penggunaan Redis. Nilai secret tidak boleh ditulis ke repository, tiket, screenshot, atau log.

## Gate deployment

Jalankan sebelum setiap deployment:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan optimize
npm ci
npm run build
composer audit --locked
npm audit --omit=dev
```

Pada Vercel, cache Laravel wajib memakai environment berikut karena filesystem aplikasi bersifat read-only:

```text
APP_CONFIG_CACHE=/tmp/config.php
APP_EVENTS_CACHE=/tmp/events.php
APP_PACKAGES_CACHE=/tmp/packages.php
APP_ROUTES_CACHE=/tmp/routes.php
APP_SERVICES_CACHE=/tmp/services.php
VIEW_COMPILED_PATH=/tmp/views
```

Konfigurasi tersebut sudah tercatat di `vercel.json`. Simulasi `php artisan optimize` terhadap direktori cache terpisah berhasil pada 16 Juli 2026.

## Secret dan cron

- `CRON_SECRET`: minimal 32 karakter acak. Kedua endpoint memakai satu nilai yang sama melalui middleware `cron.auth`.
- `TELEGRAM_WEBHOOK_SECRET`: hanya `A-Z`, `a-z`, `0-9`, `_`, atau `-` dan panjang 1–256 karakter sesuai format Telegram.
- Jangan menguji cron dengan secret pada query string. Gunakan `Authorization: Bearer <CRON_SECRET>`.
- Setelah deployment, pastikan `/cron/reminder` dan `/cron/work` ada di halaman Cron Vercel. Request tanpa token harus menghasilkan `401`.
- Setelah mengganti secret Telegram, panggil kembali `setWebhook` dengan URL `https://simak-xi.vercel.app/telegram/webhook` dan `secret_token` baru, lalu periksa `getWebhookInfo`.

## Rotasi credential

Lakukan setiap 90 hari dan segera setelah ada dugaan kebocoran atau pergantian pengelola:

1. Buat credential baru pada penyedia terkait.
2. Pasang pada environment Vercel tanpa menghapus credential lama terlebih dahulu.
3. Redeploy dan jalankan smoke test.
4. Untuk Telegram, daftarkan ulang webhook menggunakan `secret_token` baru.
5. Cabut credential lama setelah verifikasi berhasil.
6. Catat tanggal, pelaksana, dan hasil verifikasi di pencatat secret organisasi—bukan di Git.

Urutan yang disarankan: B2 key, database credential, token bot Telegram, webhook secret, lalu cron secret. Rotasi database harus memakai overlap credential atau maintenance window agar tidak memutus request aktif.

## Smoke test

Setelah deployment:

1. Login dengan akun uji production yang haknya paling rendah.
2. Buka dashboard dan pastikan ringkasan tampil tanpa error console/CSP.
3. Unggah PDF kecil yang tidak sensitif melalui alur mutasi, lalu hapus draft uji.
4. Unduh dokumen milik akun tersebut dan pastikan dokumen akun lain tetap ditolak.
5. Pastikan `/cron/reminder` dan `/cron/work` menghasilkan `401` tanpa token.
6. Jalankan kedua cron dengan Bearer token dan pastikan JSON sukses serta tidak ada kegagalan queue/Telegram di log.
7. Periksa `getWebhookInfo`: URL benar dan `pending_update_count` tetap nol.

## Keputusan Redis

Redis belum diperlukan untuk beban saat ini. Cache dan queue database tetap dipakai karena pekerjaan utama hanya terjadwal harian, pemrosesan sudah memakai chunk, dan belum ada bukti bottleneck concurrency.

Evaluasi ulang Redis jika salah satu kondisi berikut terjadi selama tujuh hari berturut-turut:

- p95 request melebihi 750 ms karena contention pada tabel `cache`, `cache_locks`, atau `jobs`;
- antrean tertunda lebih dari 100 job atau job tertua menunggu lebih dari 5 menit;
- deadlock/lock timeout database terkait cache atau queue muncul berulang;
- kebutuhan worker paralel meningkat menjadi lebih dari dua proses;
- cleanup tabel cache/queue mulai membebani database production.

Migrasi Redis harus dilakukan terpisah dengan rollback ke driver database, pengukuran sebelum/sesudah, dan pengujian queue gagal/retry.
