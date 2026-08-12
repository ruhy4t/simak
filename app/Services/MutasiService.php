<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * MutasiService - Handles business logic for mutation operations
 *
 * Extracted from MutasiController and MutasiMasukController to reduce fat controller issue.
 * Role/Authorization checks remain in controllers - this service only handles data operations.
 */
class MutasiService
{
    /**
     * Sanitize string input - remove dangerous characters
     */
    public function sanitize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove null bytes and control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Trim whitespace
        return trim($input);
    }

    /**
     * Sanitize filename - only allow safe characters
     */
    public function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    }

    /**
     * Validate and sanitize NPSN
     * Allows: digits (regular schools), P prefix (PKBM), LN- prefix (Luar Negeri)
     */
    public function sanitizeNpsn(?string $npsn): ?string
    {
        if ($npsn === null || $npsn === '' || $npsn === '-') {
            return null;
        }

        // Allow alphanumeric, dash, and underscore (for PKBM and foreign schools)
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $npsn);

        // Return uppercase to normalize
        return strtoupper($sanitized);
    }

    /**
     * Validate and sanitize NISN (digits only)
     */
    public function sanitizeNisn(?string $nisn): ?string
    {
        if ($nisn === null || $nisn === '') {
            return null;
        }

        // Remove non-digits and return
        return preg_replace('/[^0-9]/', '', $nisn);
    }

    /**
     * Generate unique Mutasi ID (Keluar)
     */
    public function generateMutasiKeluarId(): string
    {
        return $this->generateMutationId('keluar', 'mutasi_keluar', 'MK');
    }

    /**
     * Generate unique Mutasi ID (Masuk)
     */
    public function generateMutasiMasukId(): string
    {
        return $this->generateMutationId('masuk', 'mutasi_masuk', 'MM');
    }

    /**
     * Reserve a monotonically increasing number for a mutation ID.
     *
     * The sequence row is intentionally kept separate from mutation records so
     * deleting a draft/submission can never make its number available again.
     * lockForUpdate serializes concurrent requests for the same mutation type.
     */
    private function generateMutationId(string $type, string $table, string $prefix): string
    {
        return DB::transaction(function () use ($type, $table, $prefix): string {
            $sequence = DB::table('mutation_id_sequences')
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::table('mutation_id_sequences')->insertOrIgnore([
                    'type' => $type,
                    'last_value' => $this->highestExistingSequence($table, $prefix),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sequence = DB::table('mutation_id_sequences')
                    ->where('type', $type)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $sequence) {
                throw new \RuntimeException("Sequence ID mutasi {$type} tidak tersedia.");
            }

            $nextValue = (int) $sequence->last_value + 1;
            $idMutasi = $prefix.date('Ymd').str_pad((string) $nextValue, 4, '0', STR_PAD_LEFT);

            // Protect installations whose sequence table was initialized from an
            // incomplete legacy dataset. The primary key remains the final guard.
            while (DB::table($table)->where('id_mutasi', $idMutasi)->exists()) {
                $nextValue++;
                $idMutasi = $prefix.date('Ymd').str_pad((string) $nextValue, 4, '0', STR_PAD_LEFT);
            }

            if (strlen($idMutasi) > 20) {
                throw new \RuntimeException("Kapasitas ID mutasi {$type} telah terlampaui.");
            }

            DB::table('mutation_id_sequences')
                ->where('type', $type)
                ->update([
                    'last_value' => $nextValue,
                    'updated_at' => now(),
                ]);

            return $idMutasi;
        }, 5);
    }

    private function highestExistingSequence(string $table, string $prefix): int
    {
        return DB::table($table)
            ->pluck('id_mutasi')
            ->reduce(function (int $highest, string $id) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'\d{8}(\d+)$/', $id, $matches) !== 1) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);
    }
}
