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
        $count = DB::table('mutasi_keluar')->count() + 1;
        $idMutasi = 'MK' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Ensure unique
        while (DB::table('mutasi_keluar')->where('id_mutasi', $idMutasi)->exists()) {
            $count++;
            $idMutasi = 'MK' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $idMutasi;
    }

    /**
     * Generate unique Mutasi ID (Masuk)
     */
    public function generateMutasiMasukId(): string
    {
        $count = DB::table('mutasi_masuk')->count() + 1;
        $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Ensure unique
        while (DB::table('mutasi_masuk')->where('id_mutasi', $idMutasi)->exists()) {
            $count++;
            $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $idMutasi;
    }
}
