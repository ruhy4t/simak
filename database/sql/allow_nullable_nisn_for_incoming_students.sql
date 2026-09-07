-- Migrasi MySQL/MariaDB melalui HeidiSQL tanpa akses CLI.
-- Pilih database aplikasi yang benar sebelum menjalankan skrip ini.
-- Jalankan per bagian sesuai urutan. Jika ada error, berhenti dan jangan
-- lanjutkan ke pencatatan migrations sebelum ALTER TABLE berhasil.

-- 1. Pastikan database dan struktur awal.
SELECT DATABASE() AS database_aktif;
SHOW COLUMNS FROM `mutasi_masuk_siswa` LIKE 'nisn';

-- 2. Izinkan NISN kosong untuk murid luar negeri.
-- Nilai NISN yang sudah ada dan indeksnya tetap dipertahankan.
-- Aman dijalankan kembali jika kolom sudah nullable.
ALTER TABLE `mutasi_masuk_siswa`
    MODIFY COLUMN `nisn` VARCHAR(10) NULL DEFAULT NULL;

-- 3. Setelah ALTER berhasil, catat migrasi agar Laravel tidak mengulangnya.
SET @incoming_nisn_batch = (
    SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_07_000001_allow_nullable_nisn_for_incoming_students', @incoming_nisn_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_07_000001_allow_nullable_nisn_for_incoming_students'
);

-- 4. Verifikasi: Null harus YES dan catatan migrasi harus muncul satu kali.
SHOW COLUMNS FROM `mutasi_masuk_siswa` LIKE 'nisn';
SELECT `migration`, `batch` FROM `migrations`
WHERE `migration` = '2026_09_07_000001_allow_nullable_nisn_for_incoming_students';
