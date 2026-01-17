-- ============================================
-- SQL: Tambah kolom is_dinas_entry 
-- Jalankan script ini di database
-- ============================================

-- Untuk tabel mutasi_keluar
ALTER TABLE mutasi_keluar 
ADD COLUMN IF NOT EXISTS is_dinas_entry TINYINT(1) DEFAULT 0 COMMENT 'Entry dari Dinas (1=tanpa file upload)';

-- Untuk tabel mutasi_masuk
ALTER TABLE mutasi_masuk 
ADD COLUMN IF NOT EXISTS is_dinas_entry TINYINT(1) DEFAULT 0 COMMENT 'Entry dari Dinas (1=tanpa file upload)';

-- Verifikasi
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'mutasi_keluar' AND COLUMN_NAME = 'is_dinas_entry';
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'mutasi_masuk' AND COLUMN_NAME = 'is_dinas_entry';