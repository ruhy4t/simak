-- Tabel Penandatangan Surat
CREATE TABLE IF NOT EXISTS `penandatangan` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `nip` VARCHAR(30) NULL,
    `jabatan` VARCHAR(100) NOT NULL,
    `pangkat_golongan` VARCHAR(100) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default penandatangan
INSERT INTO `penandatangan` (`nama`, `nip`, `jabatan`, `pangkat_golongan`, `is_active`) VALUES
('Kepala Dinas Pendidikan', '19700101 199001 1 001', 'Kepala Dinas Pendidikan', 'Pembina Utama Madya (IV/d)', 1);

-- Tambahkan kolom surat izin jika belum ada
ALTER TABLE `mutasi_keluar` ADD COLUMN IF NOT EXISTS `nomor_surat_izin` VARCHAR(50) NULL;
ALTER TABLE `mutasi_keluar` ADD COLUMN IF NOT EXISTS `tanggal_surat_izin` DATE NULL;
ALTER TABLE `mutasi_keluar` ADD COLUMN IF NOT EXISTS `id_penandatangan` INT NULL;
ALTER TABLE `mutasi_keluar` ADD COLUMN IF NOT EXISTS `is_dinas_entry` TINYINT(1) DEFAULT 0;

ALTER TABLE `mutasi_masuk` ADD COLUMN IF NOT EXISTS `nomor_surat_izin` VARCHAR(50) NULL;
ALTER TABLE `mutasi_masuk` ADD COLUMN IF NOT EXISTS `tanggal_surat_izin` DATE NULL;
ALTER TABLE `mutasi_masuk` ADD COLUMN IF NOT EXISTS `id_penandatangan` INT NULL;
ALTER TABLE `mutasi_masuk` ADD COLUMN IF NOT EXISTS `is_dinas_entry` TINYINT(1) DEFAULT 0;
