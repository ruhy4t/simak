-- Add verification status columns for each document in mutasi_masuk_siswa
-- Run this in phpMyAdmin

ALTER TABLE `mutasi_masuk_siswa`
ADD COLUMN `verif_surat_pindah` TINYINT(1) NULL DEFAULT NULL AFTER `file_rekam_didik`,
ADD COLUMN `verif_surat_dapodik` TINYINT(1) NULL DEFAULT NULL AFTER `verif_surat_pindah`,
ADD COLUMN `verif_rekomendasi` TINYINT(1) NULL DEFAULT NULL AFTER `verif_surat_dapodik`,
ADD COLUMN `verif_ijazah_sd` TINYINT(1) NULL DEFAULT NULL AFTER `verif_rekomendasi`,
ADD COLUMN `verif_rapor` TINYINT(1) NULL DEFAULT NULL AFTER `verif_ijazah_sd`,
ADD COLUMN `verif_surat_penyaluran` TINYINT(1) NULL DEFAULT NULL AFTER `verif_rapor`,
ADD COLUMN `verif_rekam_didik` TINYINT(1) NULL DEFAULT NULL AFTER `verif_surat_penyaluran`;

-- Add verification status columns for each file in mutasi_keluar
ALTER TABLE `mutasi_keluar`
ADD COLUMN `verif_suket_keluar` TINYINT(1) NULL DEFAULT NULL AFTER `file_rapor`,
ADD COLUMN `verif_suket_dapo` TINYINT(1) NULL DEFAULT NULL AFTER `verif_suket_keluar`,
ADD COLUMN `verif_suket_diterima` TINYINT(1) NULL DEFAULT NULL AFTER `verif_suket_dapo`,
ADD COLUMN `verif_rapor` TINYINT(1) NULL DEFAULT NULL AFTER `verif_suket_diterima`;

-- Description:
-- NULL = not verified yet (will show in revision)
-- 1 = verified OK
-- 0 = needs revision (will show in revision)
