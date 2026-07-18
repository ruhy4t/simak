-- Backfill Data Awal tahun aktif dari Data Akhir tahun sebelumnya.
-- MySQL / MariaDB - siap dijalankan melalui HeidiSQL.
--
-- Pemetaan:
--   Data Akhir kelas VII  -> Data Awal kelas VIII tahun aktif
--   Data Akhir kelas VIII -> Data Awal kelas IX tahun aktif
--
-- Data Akhir yang sudah dikunci tidak diubah.

START TRANSACTION;

-- Sinkronkan tabel data siswa per tahun pelajaran.
INSERT INTO satuan_pendidikan_student_counts (
    satuan_pendidikan_id,
    academic_year_id,
    grade,
    male_count,
    female_count,
    created_at,
    updated_at
)
SELECT
    closing_counts.satuan_pendidikan_id,
    active_year.id,
    closing_counts.grade + 1,
    closing_counts.closing_male_count,
    closing_counts.closing_female_count,
    NOW(),
    NOW()
FROM academic_years AS active_year
INNER JOIN satuan_pendidikan_student_counts AS closing_counts
    ON closing_counts.academic_year_id = active_year.student_counts_rolled_over_from_id
WHERE active_year.is_active = 1
  AND active_year.student_counts_rolled_over_from_id IS NOT NULL
  AND closing_counts.grade IN (7, 8)
  AND closing_counts.closing_confirmed_at IS NOT NULL
  AND closing_counts.closing_male_count IS NOT NULL
  AND closing_counts.closing_female_count IS NOT NULL
ON DUPLICATE KEY UPDATE
    male_count = VALUES(male_count),
    female_count = VALUES(female_count),
    updated_at = NOW();

-- Sinkronkan kolom kompatibilitas pada profil sekolah.
UPDATE satuan_pendidikan AS schools
INNER JOIN (
    SELECT
        closing_counts.satuan_pendidikan_id,
        MAX(CASE
            WHEN closing_counts.grade = 7
            THEN closing_counts.closing_male_count
        END) AS grade_8_male,
        MAX(CASE
            WHEN closing_counts.grade = 7
            THEN closing_counts.closing_female_count
        END) AS grade_8_female,
        MAX(CASE
            WHEN closing_counts.grade = 8
            THEN closing_counts.closing_male_count
        END) AS grade_9_male,
        MAX(CASE
            WHEN closing_counts.grade = 8
            THEN closing_counts.closing_female_count
        END) AS grade_9_female
    FROM academic_years AS active_year
    INNER JOIN satuan_pendidikan_student_counts AS closing_counts
        ON closing_counts.academic_year_id = active_year.student_counts_rolled_over_from_id
    WHERE active_year.is_active = 1
      AND active_year.student_counts_rolled_over_from_id IS NOT NULL
      AND closing_counts.grade IN (7, 8)
      AND closing_counts.closing_confirmed_at IS NOT NULL
      AND closing_counts.closing_male_count IS NOT NULL
      AND closing_counts.closing_female_count IS NOT NULL
    GROUP BY closing_counts.satuan_pendidikan_id
    HAVING COUNT(DISTINCT closing_counts.grade) = 2
) AS promoted
    ON promoted.satuan_pendidikan_id = schools.id
SET
    schools.jumlah_siswa_8_l = promoted.grade_8_male,
    schools.jumlah_siswa_8_p = promoted.grade_8_female,
    schools.jumlah_siswa_awal_8 = promoted.grade_8_male + promoted.grade_8_female,
    schools.jumlah_siswa_9_l = promoted.grade_9_male,
    schools.jumlah_siswa_9_p = promoted.grade_9_female,
    schools.jumlah_siswa_awal_9 = promoted.grade_9_male + promoted.grade_9_female,
    schools.updated_at = NOW();

COMMIT;

-- Verifikasi hasil. Semua baris seharusnya berstatus SESUAI.
SELECT
    schools.npsn,
    schools.nama,
    source_year.name AS tahun_sumber,
    active_year.name AS tahun_aktif,
    source_counts.grade AS kelas_sumber,
    target_counts.grade AS kelas_tujuan,
    source_counts.closing_male_count AS data_akhir_l,
    target_counts.male_count AS data_awal_l,
    source_counts.closing_female_count AS data_akhir_p,
    target_counts.female_count AS data_awal_p,
    CASE
        WHEN target_counts.male_count = source_counts.closing_male_count
         AND target_counts.female_count = source_counts.closing_female_count
        THEN 'SESUAI'
        ELSE 'TIDAK SESUAI'
    END AS status_sinkron
FROM academic_years AS active_year
INNER JOIN academic_years AS source_year
    ON source_year.id = active_year.student_counts_rolled_over_from_id
INNER JOIN satuan_pendidikan_student_counts AS source_counts
    ON source_counts.academic_year_id = source_year.id
INNER JOIN satuan_pendidikan AS schools
    ON schools.id = source_counts.satuan_pendidikan_id
LEFT JOIN satuan_pendidikan_student_counts AS target_counts
    ON target_counts.satuan_pendidikan_id = source_counts.satuan_pendidikan_id
   AND target_counts.academic_year_id = active_year.id
   AND target_counts.grade = source_counts.grade + 1
WHERE active_year.is_active = 1
  AND source_counts.grade IN (7, 8)
  AND source_counts.closing_confirmed_at IS NOT NULL
ORDER BY schools.nama, source_counts.grade;
