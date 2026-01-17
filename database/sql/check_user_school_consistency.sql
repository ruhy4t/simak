<<<<<<< HEAD
-- =============================================================================
-- DATA CLEANUP CHECK: Users linked to External Schools
-- =============================================================================
-- 
-- Skenario yang Benar:
-- 1. Satuan Pendidikan dengan is_local = true  → Bisa jadi akun pengguna
-- 2. Satuan Pendidikan dengan is_local = false → Hanya referensi, TIDAK boleh jadi pengguna
--
-- Query ini mencari pengguna yang NPSN-nya terhubung ke sekolah luar kota
-- =============================================================================

-- 1. Cek pengguna yang NPSN-nya ada di satuan_pendidikan tapi is_local = false
SELECT 
    u.id,
    u.username,
    u.nama_pengguna,
    u.npsn,
    u.nama_sekolah,
    u.role,
    u.status,
    sp.nama AS nama_di_satpen,
    sp.kota,
    sp.is_local
FROM users u
LEFT JOIN satuan_pendidikan sp ON u.npsn = sp.npsn
WHERE u.role IN ('Sekolah', 'Operator')
  AND u.npsn IS NOT NULL
  AND sp.is_local = 0;

-- 2. Statistik: Berapa banyak pengguna yang terhubung ke sekolah lokal vs eksternal?
SELECT 
    CASE 
        WHEN sp.is_local = 1 THEN 'Lokal (Kota Bogor)'
        WHEN sp.is_local = 0 THEN 'Eksternal (Luar Kota)'
        ELSE 'NPSN Tidak Ada di Database'
    END AS kategori_sekolah,
    COUNT(*) AS jumlah_pengguna
FROM users u
LEFT JOIN satuan_pendidikan sp ON u.npsn = sp.npsn
WHERE u.role IN ('Sekolah', 'Operator')
  AND u.npsn IS NOT NULL
GROUP BY 
    CASE 
        WHEN sp.is_local = 1 THEN 'Lokal (Kota Bogor)'
        WHEN sp.is_local = 0 THEN 'Eksternal (Luar Kota)'
        ELSE 'NPSN Tidak Ada di Database'
    END;

-- 3. Jika ada pengguna yang perlu dihapus (NPSN dari sekolah eksternal):
-- HATI-HATI: Jalankan query ini HANYA jika yakin ingin menghapus
-- DELETE FROM users 
-- WHERE role IN ('Sekolah', 'Operator') 
--   AND npsn IN (SELECT npsn FROM satuan_pendidikan WHERE is_local = 0);
=======
-- =============================================================================
-- DATA CLEANUP CHECK: Users linked to External Schools
-- =============================================================================
-- 
-- Skenario yang Benar:
-- 1. Satuan Pendidikan dengan is_local = true  → Bisa jadi akun pengguna
-- 2. Satuan Pendidikan dengan is_local = false → Hanya referensi, TIDAK boleh jadi pengguna
--
-- Query ini mencari pengguna yang NPSN-nya terhubung ke sekolah luar kota
-- =============================================================================

-- 1. Cek pengguna yang NPSN-nya ada di satuan_pendidikan tapi is_local = false
SELECT 
    u.id,
    u.username,
    u.nama_pengguna,
    u.npsn,
    u.nama_sekolah,
    u.role,
    u.status,
    sp.nama AS nama_di_satpen,
    sp.kota,
    sp.is_local
FROM users u
LEFT JOIN satuan_pendidikan sp ON u.npsn = sp.npsn
WHERE u.role IN ('Sekolah', 'Operator')
  AND u.npsn IS NOT NULL
  AND sp.is_local = 0;

-- 2. Statistik: Berapa banyak pengguna yang terhubung ke sekolah lokal vs eksternal?
SELECT 
    CASE 
        WHEN sp.is_local = 1 THEN 'Lokal (Kota Bogor)'
        WHEN sp.is_local = 0 THEN 'Eksternal (Luar Kota)'
        ELSE 'NPSN Tidak Ada di Database'
    END AS kategori_sekolah,
    COUNT(*) AS jumlah_pengguna
FROM users u
LEFT JOIN satuan_pendidikan sp ON u.npsn = sp.npsn
WHERE u.role IN ('Sekolah', 'Operator')
  AND u.npsn IS NOT NULL
GROUP BY 
    CASE 
        WHEN sp.is_local = 1 THEN 'Lokal (Kota Bogor)'
        WHEN sp.is_local = 0 THEN 'Eksternal (Luar Kota)'
        ELSE 'NPSN Tidak Ada di Database'
    END;

-- 3. Jika ada pengguna yang perlu dihapus (NPSN dari sekolah eksternal):
-- HATI-HATI: Jalankan query ini HANYA jika yakin ingin menghapus
-- DELETE FROM users 
-- WHERE role IN ('Sekolah', 'Operator') 
--   AND npsn IN (SELECT npsn FROM satuan_pendidikan WHERE is_local = 0);
>>>>>>> 9466a07 (Upload project pertama kali)
