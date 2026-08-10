# SIMAK

SIMAK adalah aplikasi Sistem Informasi Mutasi Masuk dan Keluar murid untuk Dinas Pendidikan Kota Bogor.

## Versi Aplikasi

Versi saat ini: **2.7.7**

Perubahan utama versi 2.6.0:
- Menambahkan prefill data mutasi masuk berdasarkan NISN dari mutasi keluar Kota Bogor.
- Membatasi sumber prefill hanya pada data mutasi keluar berstatus `Selesai`.
- Mengisi identitas murid dan sekolah asal secara otomatis dengan data referensi yang tervalidasi.

Perubahan utama versi 2.5.0:
- Menambahkan template Word SRIKANDI untuk mutasi masuk dengan placeholder bertanda `$`.
- Membuat surat potret dan lampiran tabel lanskap dari data mutasi serta murid yang tersimpan di aplikasi.
- Menjaga kompatibilitas dokumen dengan margin OOXML yang valid dan garis tabel lampiran yang konsisten.

Perubahan utama versi 2.4.0:
- Menyeragamkan istilah antarmuka dari siswa menjadi murid.
- Memperkuat keamanan, cache serverless, navigasi AJAX, dan performa laporan.
- Mewajibkan profil sekolah, data murid awal, dan data akhir lengkap sebelum Sekolah atau Operator mengusulkan mutasi.

Perubahan utama versi 2.3.3:
- Mempercepat login dengan memperbaiki pencatatan audit yang sebelumnya selalu gagal karena ketidaksesuaian timestamp.
- Menambahkan pilihan jenis akun dan mewajibkan NPSN untuk login Sekolah dan Operator.
- Mengizinkan username Operator yang sama pada sekolah berbeda dengan pembeda NPSN.

Perubahan utama versi 2.2.0:
- Menambahkan grafik kohort data awal, mutasi, data akhir aktual, dan selisih rekonsiliasi.
- Menambahkan pendataan individual murid keluar non-mutasi tanpa unggah dokumen atau persetujuan Dinas.
- Mewajibkan NISN 10 digit dan mencegah duplikasi per sekolah serta tahun pelajaran.
- Menampilkan data individual hanya untuk sekolah terkait dan Dinas; publik tetap menerima agregat.

Perubahan utama versi 2.1.0:
- Menambahkan statistik mutasi publik tanpa login dengan data agregat, cache, dan rate limit.
- Mengoptimalkan grafik laporan agar memakai bundle Chart.js yang dimuat secara dinamis.
- Memperbaiki template PDF rekap dan regression test terkait laporan.
- Mengaktifkan login persisten 30 hari melalui opsi "Ingat saya".

Dasar versi 2.0.1:
- Memperketat autentikasi endpoint cron menggunakan Bearer token.
- Menambahkan validasi secret token untuk webhook Telegram.
- Mengaktifkan verifikasi TLS pada seluruh koneksi Telegram.
- Menambahkan jadwal Vercel Cron dan optimasi query tugas harian.
- Memperbarui dependency PHP untuk menutup advisory keamanan.

Dasar versi 2.0.0:
- Upgrade ke Laravel 12.
- Upgrade frontend build stack ke Tailwind CSS 4 dan Vite 8.
- Menambahkan baseline test suite.
- Menghapus QRCode generator karena belum dibutuhkan.
- Menyesuaikan konfigurasi Vercel Preview agar asset mengikuti domain deployment aktif.

Versi aplikasi dikontrol lewat environment variable:

```env
APP_VERSION=2.7.7
```

Jika tidak diisi, aplikasi memakai default dari `config/app.php`.

## Stack Utama

- PHP: `^8.2` tested dengan PHP `8.3.x`
- Laravel: `^12.0`
- Node.js: `22.x`
- Vite: `^8.0.13`
- Tailwind CSS: `^4.3.0`
- PHPUnit: `^11.5`
- Database: MySQL compatible, termasuk Aiven MySQL
- Storage: local atau Backblaze B2 melalui S3-compatible driver
- Deploy target: Vercel dengan `vercel-php@0.9.0` (PHP 8.5.x)

## Setup Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database dan storage di `.env`, lalu jalankan:

```bash
php artisan migrate
npm run build
php artisan serve
```

## Verifikasi

Sebelum deploy atau merge perubahan besar, jalankan:

```bash
php artisan test
php artisan route:list
composer validate --no-check-publish
composer audit
npm audit
npm run build
```

## Deploy Vercel

Project ini memakai `vercel.json` dengan:

- Build command: `npm run build`
- Output directory: `public`
- PHP runtime: `vercel-php@0.9.0` (PHP 8.5.x)

Untuk Preview Deployment, jangan hardcode `APP_URL` atau `ASSET_URL` di `vercel.json`. Entry point `api/index.php` akan mengatur URL aplikasi dan asset berdasarkan domain Vercel yang sedang aktif.

Environment variable penting di Vercel:

```env
APP_NAME=SIMAK
APP_VERSION=2.7.7
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

FILESYSTEM_DISK=b2
B2_ACCESS_KEY_ID=
B2_SECRET_ACCESS_KEY=
B2_REGION=
B2_BUCKET=
B2_ENDPOINT=
```

### Redis opsional di Vercel

Vercel mendukung Redis melalui integrasi Marketplace seperti Upstash Redis atau Redis Cloud. Aplikasi tetap memakai cache database secara default agar deployment lama tidak berubah.

Setelah Redis dibuat, hubungkan resource tersebut ke project Vercel, pastikan URL koneksi TLS tersedia sebagai `REDIS_URL`, lalu tambahkan:

```env
REDIS_CLIENT=predis
CACHE_STORE=redis_failover
REDIS_URL=rediss://default:password@host:port
REDIS_CACHE_CONNECTION=cache
REDIS_CACHE_DB=0
REDIS_CONNECT_TIMEOUT=2
REDIS_READ_TIMEOUT=2
```

Gunakan region Redis yang dekat dengan region Function dan database. Uji koneksi pada Preview Deployment sebelum mengubah environment Production. Mode `redis_failover` akan menggunakan cache database jika Redis tidak tersedia.

## Backup Stabil

Backup point sebelum rangkaian upgrade besar:

- Tag: `stable-before-upgrade-2026-05-20`
- Branch: `backup/stable-before-upgrade-2026-05-20`

## Catatan

Branch upgrade saat ini: `upgrade/laravel-12`.

Sebelum merge ke `main`, gunakan Preview Deployment Vercel untuk mengecek login, dashboard, mutasi masuk/keluar, laporan, upload arsip B2, dan tampilan utama.
