<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RegionCacheService
{
    /**
     * Cache duration in seconds (30 days for local DB data)
     */
    protected const TTL_LOCAL = 2592000;

    /**
     * Cache duration for external API fallback (24 hours)
     */
    protected const TTL_EXTERNAL = 86400;

    /**
     * Get Provinces
     * Priority: Local DB -> Cache -> External API
     */
    public function getProvinces()
    {
        // 1. Try Local DB (Cached)
        $provinces = Cache::remember('ref_provinces_local_db', self::TTL_LOCAL, function () {
            if (! Schema::hasTable('ref_provinsi')) {
                return null;
            }
            return DB::table('ref_provinsi')
                ->select(['id', 'nama as name'])
                ->orderBy('nama')
                ->get();
        });

        if ($provinces?->isNotEmpty()) {
            return ['success' => true, 'source' => 'local_db', 'data' => $provinces];
        }

        // 2. Fallback to External API (Cached)
        return Cache::remember('ref_provinces_external', self::TTL_EXTERNAL, function () {
            try {
                $response = Http::timeout(5)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
                if ($response->successful()) {
                    return ['success' => true, 'source' => 'external', 'data' => $response->json()];
                }
            } catch (\Exception $e) {
                Log::error('External province fetch failed: ' . $e->getMessage());
            }
            return ['success' => false, 'data' => [], 'message' => 'Gagal memuat data provinsi'];
        });
    }

    /**
     * Get Regencies by Province ID
     */
    public function getRegencies($provinceId)
    {
        $regencies = Cache::remember("ref_regencies_local_{$provinceId}", self::TTL_LOCAL, function () use ($provinceId) {
            if (! Schema::hasTable('ref_kabupaten')) {
                return null;
            }
            return DB::table('ref_kabupaten')
                ->where('province_id', $provinceId)
                ->select(['id', 'nama as name'])
                ->orderBy('nama')
                ->get();
        });

        if ($regencies?->isNotEmpty()) {
            return ['success' => true, 'source' => 'local_db', 'data' => $regencies];
        }

        return Cache::remember("ref_regencies_external_{$provinceId}", self::TTL_EXTERNAL, function () use ($provinceId) {
            try {
                $response = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceId}.json");
                if ($response->successful()) {
                    return ['success' => true, 'source' => 'external', 'data' => $response->json()];
                }
            } catch (\Exception $e) {
                Log::error('External regency fetch failed: ' . $e->getMessage());
            }
            return ['success' => false, 'data' => [], 'message' => 'Gagal memuat data kabupaten'];
        });
    }

    /**
     * Get Districts (Kecamatan) by Regency ID
     */
    public function getDistricts($regencyId)
    {
        $districts = Cache::rememberForever("ref_districts_{$regencyId}", function () use ($regencyId) {
            if (Schema::hasTable('ref_kecamatan')) {
                return DB::table('ref_kecamatan')
                    ->where('regency_id', $regencyId)
                    ->select(['id', 'regency_id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            }
            return collect([]);
        });

        return [
            'success' => $districts->isNotEmpty(),
            'source' => 'local',
            'data' => $districts
        ];
    }

    /**
     * Get Villages (Kelurahan) by District ID
     */
    public function getVillages($districtId)
    {
        $villages = Cache::rememberForever("ref_villages_{$districtId}", function () use ($districtId) {
            if (Schema::hasTable('ref_kelurahan')) {
                return DB::table('ref_kelurahan')
                    ->where('district_id', $districtId)
                    ->select(['id', 'district_id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            }
            return collect([]);
        });

        return [
            'success' => $villages->isNotEmpty(),
            'source' => 'local',
            'data' => $villages
        ];
    }

    /**
     * Clear All Region Caches
     * Call this when syncing or resetting data
     */
    public function clearAll()
    {
        Cache::forget('ref_provinces_local_db');
        Cache::forget('ref_provinces_external');
        // Wildcard clearing is not supported by standard drivers easily, 
        // but since we name keys predictably, we can't easily clear "all regencies" without tagging.
        // For simple file cache, we can just rely on 'flush' or strict key management.
        // For now, we assume this is rare.
        Cache::flush(); // WARNING: This clears ALL app cache. Use with caution.
        // Ideally we use tags: Cache::tags(['regions'])->flush(); but database/file drivers don't support tags.
        // Alternative: timestamp versioning or just accepting that specific sub-caches linger until TTL.
        // However, for Region Sync, clearing everything is usually acceptable in this context.
    }

    /**
     * Clear Specific Child Cache
     * Called when adding/deleting a specific item (e.g. adding a Kecamatan)
     */
    public function clearCacheFor($type, $parentId = null)
    {
        if ($type === 'provinsi') {
            Cache::forget('ref_provinces_local_db');
        } elseif ($type === 'kabupaten') {
            if ($parentId)
                Cache::forget("ref_regencies_local_{$parentId}");
        } elseif ($type === 'kecamatan') {
            if ($parentId)
                Cache::forget("ref_districts_{$parentId}");
        } elseif ($type === 'kelurahan') {
            if ($parentId)
                Cache::forget("ref_villages_{$parentId}");
        }
    }
}
