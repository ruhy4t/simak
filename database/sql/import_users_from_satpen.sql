-- ============================================
-- SIMAK - Import Users (Role: Sekolah)
-- Username: NPSN | Password: NPSNFa*
-- ============================================
-- Password hash dibuat dengan: password_hash('NPSNFa*', PASSWORD_BCRYPT)
-- Contoh: 20220385Fa* -> $2y$12$...

-- Jalankan query ini setelah import satuan_pendidikan

INSERT INTO `users` (`username`, `password`, `role`, `nama_pengguna`, `no_kontak`, `npsn`, `nama_sekolah`, `alamat`, `status`, `last_login_at`, `failed_login_attempts`, `locked_until`, `remember_token`, `created_at`, `updated_at`)
SELECT 
    sp.npsn AS username,
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4p0d9W6EJjIvWEHu' AS password, -- Default password: NPSNFa*
    'Sekolah' AS role,
    CONCAT('Admin ', sp.nama) AS nama_pengguna,
    NULL AS no_kontak,
    sp.npsn,
    sp.nama AS nama_sekolah,
    sp.alamat_lengkap AS alamat,
    'Aktif' AS status,
    NULL AS last_login_at,
    0 AS failed_login_attempts,
    NULL AS locked_until,
    NULL AS remember_token,
    NOW() AS created_at,
    NOW() AS updated_at
FROM satuan_pendidikan sp
WHERE sp.npsn NOT IN (SELECT username FROM users WHERE username = sp.npsn);

-- ============================================
-- PENTING: Setelah import, update password dengan benar!
-- Jalankan perintah PHP ini di server:
-- 
-- php artisan tinker
-- >>> $users = \App\Models\User::where('role', 'Sekolah')->get();
-- >>> foreach($users as $u) { $u->password = bcrypt($u->npsn . 'Fa*'); $u->save(); }
-- 
-- ============================================