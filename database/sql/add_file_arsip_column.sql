<<<<<<< HEAD
-- Script ini untuk menambahkan kolom Arsip Digital secara manual di phpMyAdmin
-- Jalankan di tab "SQL" pada database Anda

-- 1. Tambahkan kolom ke tabel mutasi_keluar
ALTER TABLE `mutasi_keluar` 
ADD COLUMN `file_arsip_scan` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path file scan arsip digital' 
AFTER `catatan_berkas`;

-- 2. Tambahkan kolom ke tabel mutasi_masuk
ALTER TABLE `mutasi_masuk` 
ADD COLUMN `file_arsip_scan` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path file scan arsip digital' 
AFTER `catatan_berkas`;
=======
-- Script ini untuk menambahkan kolom Arsip Digital secara manual di phpMyAdmin
-- Jalankan di tab "SQL" pada database Anda

-- 1. Tambahkan kolom ke tabel mutasi_keluar
ALTER TABLE `mutasi_keluar` 
ADD COLUMN `file_arsip_scan` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path file scan arsip digital' 
AFTER `catatan_berkas`;

-- 2. Tambahkan kolom ke tabel mutasi_masuk
ALTER TABLE `mutasi_masuk` 
ADD COLUMN `file_arsip_scan` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path file scan arsip digital' 
AFTER `catatan_berkas`;
>>>>>>> 9466a07 (Upload project pertama kali)
