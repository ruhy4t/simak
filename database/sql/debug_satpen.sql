-- SIMAK Debug Query
-- Jalankan query ini di phpMyAdmin untuk cek data

-- 1. Cek data user operator1
SELECT id, username, npsn, nama_sekolah, alamat, role 
FROM users 
WHERE username = 'operator1' OR npsn = '99999999';

-- 2. Cek data satuan_pendidikan untuk NPSN 99999999
SELECT npsn, nama, alamat_jalan, kelurahan, kecamatan, kota, provinsi, alamat_lengkap 
FROM satuan_pendidikan 
WHERE npsn = '99999999';

-- 3. Jika alamat_lengkap kosong, update dengan data manual:
-- UPDATE satuan_pendidikan 
-- SET alamat_lengkap = CONCAT(
--     COALESCE(alamat_jalan, ''), 
--     ', Kel. ', COALESCE(kelurahan, ''),
--     ', Kec. ', COALESCE(kecamatan, ''),
--     ', ', COALESCE(kota, ''),
--     ', ', COALESCE(provinsi, '')
-- )
-- WHERE npsn = '99999999' AND (alamat_lengkap IS NULL OR alamat_lengkap = '');

-- 4. Cek semua kolom di satuan_pendidikan
DESCRIBE satuan_pendidikan;

-- 5. Cek sample data
SELECT * FROM satuan_pendidikan LIMIT 3;