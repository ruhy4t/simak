-- Migration: Add tanggal_selesai column for Proses TTD flow
-- Run this on production database

-- For mutasi_keluar table
ALTER TABLE mutasi_keluar ADD COLUMN tanggal_selesai DATE NULL AFTER tanggal_surat_izin;

-- For mutasi_masuk table  
ALTER TABLE mutasi_masuk ADD COLUMN tanggal_selesai DATE NULL AFTER tanggal_surat_izin;

-- Update existing "Selesai" records to have tanggal_selesai = tanggal_surat_izin
UPDATE mutasi_keluar SET tanggal_selesai = tanggal_surat_izin WHERE status = 'Selesai' AND tanggal_selesai IS NULL;
UPDATE mutasi_masuk SET tanggal_selesai = tanggal_surat_izin WHERE status = 'Selesai' AND tanggal_selesai IS NULL;
