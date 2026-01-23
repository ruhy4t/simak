INSTRUKSI PENGALIH (REDIRECT) DARI HOSTING LAMA KE VERCEL

Jika Anda memiliki hosting lama (misal di cPanel) dan ingin semua pengunjung otomatis diarahkan ke aplikasi Vercel baru, gunakan salah satu file di folder ini.

OPSI 1: Menggunakan PHP (Paling Mudah)
1. Rename file 'index.php' yang ada di folder ini.
2. Upload ke folder 'public_html' di hosting lama Anda.
3. Hapus atau rename file 'index.php' atau 'index.html' yang lama.

OPSI 2: Menggunakan .htaccess (Untuk Server Apache)
1. Upload file '.htaccess' ke folder 'public_html' di hosting lama Anda.
2. Jika sudah ada file .htaccess, tambahkan isinya di bagian paling atas.

Dengan cara ini, siapapun yang membuka link lama (misal: domainlama.com/mutasi) akan otomatis pindah ke https://simak-xi.vercel.app/mutasi
