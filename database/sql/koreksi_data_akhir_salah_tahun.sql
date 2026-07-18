-- Koreksi Data Akhir yang terlanjur disimpan pada tahun pelajaran aktif.
-- MySQL / MariaDB - siap diimpor melalui HeidiSQL.
--
-- Proses:
-- 1. Menentukan tahun aktif dan tahun sebelumnya.
-- 2. Memindahkan Data Akhir yang salah tersimpan pada tahun aktif ke tahun sebelumnya.
-- 3. Mengosongkan Data Akhir tahun aktif agar tidak terkunci secara keliru.
-- 4. Mempromosikan VII -> VIII dan VIII -> IX sebagai Data Awal tahun aktif.

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_academic_year_pair;
CREATE TEMPORARY TABLE tmp_academic_year_pair (
    active_year_id BIGINT UNSIGNED NOT NULL,
    source_year_id BIGINT UNSIGNED NULL,
    PRIMARY KEY (active_year_id)
) ENGINE = InnoDB;

INSERT INTO tmp_academic_year_pair (
    active_year_id,
    source_year_id
)
SELECT
    active_year.id AS active_year_id,
    COALESCE(
        active_year.student_counts_rolled_over_from_id,
        (
            SELECT previous_year.id
            FROM academic_years AS previous_year
            WHERE previous_year.id <> active_year.id
              AND (
                    previous_year.starts_on < active_year.starts_on
                    OR (
                        active_year.starts_on IS NULL
                        AND previous_year.name < active_year.name
                    )
              )
            ORDER BY previous_year.starts_on DESC, previous_year.name DESC
            LIMIT 1
        )
    ) AS source_year_id
FROM academic_years AS active_year
WHERE active_year.is_active = 1;

DROP TEMPORARY TABLE IF EXISTS tmp_misplaced_closing_counts;
CREATE TEMPORARY TABLE tmp_misplaced_closing_counts (
    active_year_id BIGINT UNSIGNED NOT NULL,
    source_year_id BIGINT UNSIGNED NOT NULL,
    satuan_pendidikan_id BIGINT UNSIGNED NOT NULL,
    grade TINYINT UNSIGNED NOT NULL,
    closing_male_count INT UNSIGNED NOT NULL,
    closing_female_count INT UNSIGNED NOT NULL,
    closing_confirmed_at TIMESTAMP NOT NULL,
    PRIMARY KEY (
        active_year_id,
        source_year_id,
        satuan_pendidikan_id,
        grade
    )
) ENGINE = InnoDB;

INSERT INTO tmp_misplaced_closing_counts (
    active_year_id,
    source_year_id,
    satuan_pendidikan_id,
    grade,
    closing_male_count,
    closing_female_count,
    closing_confirmed_at
)
SELECT
    year_pair.active_year_id,
    year_pair.source_year_id,
    active_counts.satuan_pendidikan_id,
    active_counts.grade,
    active_counts.closing_male_count,
    active_counts.closing_female_count,
    active_counts.closing_confirmed_at
FROM tmp_academic_year_pair AS year_pair
INNER JOIN satuan_pendidikan_student_counts AS active_counts
    ON active_counts.academic_year_id = year_pair.active_year_id
INNER JOIN satuan_pendidikan_student_counts AS source_counts
    ON source_counts.academic_year_id = year_pair.source_year_id
   AND source_counts.satuan_pendidikan_id = active_counts.satuan_pendidikan_id
   AND source_counts.grade = active_counts.grade
WHERE year_pair.source_year_id IS NOT NULL
  AND active_counts.grade IN (7, 8, 9)
  AND active_counts.closing_confirmed_at IS NOT NULL
  AND active_counts.closing_male_count IS NOT NULL
  AND active_counts.closing_female_count IS NOT NULL
  AND source_counts.closing_confirmed_at IS NULL;

-- Salin Data Akhir yang salah tahun ke tahun sebelumnya.
UPDATE satuan_pendidikan_student_counts AS source_counts
INNER JOIN tmp_misplaced_closing_counts AS misplaced
    ON misplaced.source_year_id = source_counts.academic_year_id
   AND misplaced.satuan_pendidikan_id = source_counts.satuan_pendidikan_id
   AND misplaced.grade = source_counts.grade
SET
    source_counts.closing_male_count = misplaced.closing_male_count,
    source_counts.closing_female_count = misplaced.closing_female_count,
    source_counts.closing_confirmed_at = misplaced.closing_confirmed_at,
    source_counts.updated_at = NOW();

-- Hapus Data Akhir dari tahun aktif hanya untuk baris yang berhasil dipindahkan.
UPDATE satuan_pendidikan_student_counts AS active_counts
INNER JOIN tmp_misplaced_closing_counts AS misplaced
    ON misplaced.active_year_id = active_counts.academic_year_id
   AND misplaced.satuan_pendidikan_id = active_counts.satuan_pendidikan_id
   AND misplaced.grade = active_counts.grade
SET
    active_counts.closing_male_count = NULL,
    active_counts.closing_female_count = NULL,
    active_counts.closing_confirmed_at = NULL,
    active_counts.updated_at = NOW();

-- Promosikan Data Akhir tahun sebelumnya ke Data Awal tahun aktif.
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
    source_counts.satuan_pendidikan_id,
    year_pair.active_year_id,
    source_counts.grade + 1,
    source_counts.closing_male_count,
    source_counts.closing_female_count,
    NOW(),
    NOW()
FROM tmp_academic_year_pair AS year_pair
INNER JOIN satuan_pendidikan_student_counts AS source_counts
    ON source_counts.academic_year_id = year_pair.source_year_id
WHERE year_pair.source_year_id IS NOT NULL
  AND source_counts.grade IN (7, 8)
  AND source_counts.closing_confirmed_at IS NOT NULL
  AND source_counts.closing_male_count IS NOT NULL
  AND source_counts.closing_female_count IS NOT NULL
ON DUPLICATE KEY UPDATE
    male_count = VALUES(male_count),
    female_count = VALUES(female_count),
    updated_at = NOW();

-- Sinkronkan kolom kompatibilitas pada profil sekolah.
UPDATE satuan_pendidikan AS schools
INNER JOIN (
    SELECT
        source_counts.satuan_pendidikan_id,
        MAX(CASE WHEN source_counts.grade = 7 THEN source_counts.closing_male_count END) AS grade_8_male,
        MAX(CASE WHEN source_counts.grade = 7 THEN source_counts.closing_female_count END) AS grade_8_female,
        MAX(CASE WHEN source_counts.grade = 8 THEN source_counts.closing_male_count END) AS grade_9_male,
        MAX(CASE WHEN source_counts.grade = 8 THEN source_counts.closing_female_count END) AS grade_9_female
    FROM tmp_academic_year_pair AS year_pair
    INNER JOIN satuan_pendidikan_student_counts AS source_counts
        ON source_counts.academic_year_id = year_pair.source_year_id
    WHERE year_pair.source_year_id IS NOT NULL
      AND source_counts.grade IN (7, 8)
      AND source_counts.closing_confirmed_at IS NOT NULL
      AND source_counts.closing_male_count IS NOT NULL
      AND source_counts.closing_female_count IS NOT NULL
    GROUP BY source_counts.satuan_pendidikan_id
    HAVING COUNT(DISTINCT source_counts.grade) = 2
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

-- Ringkasan baris yang dipindahkan dari tahun aktif ke tahun sebelumnya.
SELECT
    COUNT(DISTINCT satuan_pendidikan_id) AS jumlah_sekolah_dikoreksi,
    COUNT(*) AS jumlah_baris_dipindahkan
FROM tmp_misplaced_closing_counts;

-- Verifikasi hasil promosi. Semua baris seharusnya berstatus SESUAI.
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
FROM tmp_academic_year_pair AS year_pair
INNER JOIN academic_years AS active_year
    ON active_year.id = year_pair.active_year_id
INNER JOIN academic_years AS source_year
    ON source_year.id = year_pair.source_year_id
INNER JOIN satuan_pendidikan_student_counts AS source_counts
    ON source_counts.academic_year_id = source_year.id
INNER JOIN satuan_pendidikan AS schools
    ON schools.id = source_counts.satuan_pendidikan_id
LEFT JOIN satuan_pendidikan_student_counts AS target_counts
    ON target_counts.satuan_pendidikan_id = source_counts.satuan_pendidikan_id
   AND target_counts.academic_year_id = active_year.id
   AND target_counts.grade = source_counts.grade + 1
WHERE source_counts.grade IN (7, 8)
  AND source_counts.closing_confirmed_at IS NOT NULL
ORDER BY schools.nama, source_counts.grade;

DROP TEMPORARY TABLE IF EXISTS tmp_misplaced_closing_counts;
DROP TEMPORARY TABLE IF EXISTS tmp_academic_year_pair;
