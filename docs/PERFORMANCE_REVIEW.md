# Tinjauan performa aplikasi

Tanggal: 7 September 2026. Pemeriksaan kode dan pengujian lokal, bukan benchmark
latensi produksi Vercel/Aiven.

## Area yang diperiksa

- Dashboard dan laporan: agregasi, cache berdasarkan pengguna/filter,
  pemeriksaan schema, dan pagination daftar mutasi.
- Formulir mutasi: permintaan referensi wilayah, pilihan mandiri tiap murid,
  draft, upload, dan pratinjau dokumen.
- Tampilan: bundle JavaScript/CSS, grafik, gambar, PDF, serta navigasi AJAX.
- Runtime: entry point Vercel, konfigurasi cache/CDN, middleware sesi,
  pengaturan, hari libur, dan pemeriksaan akses.

## Optimasi yang diterapkan

| Area | Perubahan | Bukti lokal |
| --- | --- | --- |
| Grafik dashboard/laporan | Registrasi hanya grafik garis, batang, donat, skala dan plugin yang digunakan. Library tetap dynamic import. | Chunk grafik 200,40 → 186,20 KB; gzip 68,76 → 64,88 KB. Ketiga jenis grafik diuji render, update, destroy. |
| PDF | Native `loading="lazy"` pada komponen viewer dan iframe halaman revisi. | URL, akses dokumen, ukuran iframe, dan tautan tab baru tetap digunakan. Browser menentukan kapan dokumen dekat viewport mulai dimuat. |
| Gambar tanda tangan di daftar | Lazy loading dan decoding asynchronous. | Template surat/cetak tidak diubah. |
| Referensi wilayah server | Pemeriksaan tabel hanya ketika cache lokal belum tersedia. | Cache hangat dengan driver array menghasilkan nol query, termasuk query schema; perubahan data dan fallback eksternal diuji. Dengan cache database tetap ada query pembacaan cache. |
| Regresi JavaScript | Tes formulir/grafik dijalankan oleh `npm test` dan CI. | Pilihan wilayah per murid, respons terlambat, kegagalan/retry, serta grafik tercakup. |

Optimasi sebelumnya juga tetap berlaku: pengaturan dimemoisasi per request,
hasil bukan-hari-libur disimpan di cache, dan formulir murid berbagi permintaan
referensi berdasarkan URL tanpa berbagi pilihan individual.

## Batas penerapan lazy loading

Lazy loading mengurangi beban awal, bukan mempercepat query server sebelum
HTML diterima. Komponen di luar viewport baru dimuat ketika mendekati layar.
Data utama formulir, validasi, otorisasi, dan gambar surat yang perlu dicetak
tidak ditunda. Grafik langsung diinisialisasi pada halaman yang memerlukannya
agar perilaku cetak dan navigasi AJAX tetap sama.

Tidak diperlukan migrasi database, dependensi baru, atau pengubahan pengaturan
server. Cache halaman privat dan aturan sesi tetap dipertahankan. Cache agregasi
dan pagination yang sudah ada tidak diubah tanpa bukti bottleneck tambahan.

## Verifikasi

Jalankan `php artisan test --compact`, `npm test`, dan `npm run build`.
Di PowerShell yang membatasi npm.ps1, gunakan `npm.cmd`.
Hasil setelah perubahan: 155 tes PHP lulus (1.193 assertions), 11 tes
JavaScript lulus, dan build produksi berhasil.

Sesudah deployment, ukur Network/Performance browser pada login, dashboard,
daftar mutasi, formulir beberapa murid, revisi dokumen, dan laporan. Bandingkan
request pertama dan berikutnya, TTFB, jumlah request, transfer size, dan waktu
tampil. Log `http.request`/`database.slow_query` yang sudah tersedia membantu
membedakan waktu query dari koneksi/serverless. Kecepatan produksi belum dapat
disimpulkan hanya dari ukuran bundle atau durasi tes lokal.
