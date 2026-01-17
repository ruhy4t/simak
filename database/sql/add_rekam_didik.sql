-- Add file_rekam_didik column to mutasi_masuk_siswa
ALTER TABLE `mutasi_masuk_siswa` 
ADD COLUMN `file_rekam_didik` VARCHAR(255) NULL AFTER `file_surat_rekomendasi`;