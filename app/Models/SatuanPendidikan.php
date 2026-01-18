<?php

namespace App\Models;

use App\Observers\SatuanPendidikanObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([SatuanPendidikanObserver::class])]
class SatuanPendidikan extends Model
{
    use HasFactory;

    protected $table = 'satuan_pendidikan';

    protected $fillable = [
        'npsn',
        'nama',
        'bentuk_pendidikan',
        'status_sp',
        'alamat_jalan',
        'kelurahan',
        'kecamatan',
        'kota',
        'provinsi',
        'alamat_lengkap',
        'is_local',
        'kop_surat_path',
        'kepala_sekolah_nama',
        'kepala_sekolah_nip',
        'jumlah_siswa_awal_7',
        'jumlah_siswa_awal_8',
        'jumlah_siswa_awal_9',
    ];

    protected $casts = [
        'is_local' => 'boolean',
    ];

    // ========== SCOPES ==========

    /**
     * Scope untuk SMP Kota Bogor saja
     */
    public function scopeLocal($query)
    {
        return $query->where('is_local', true);
    }

    /**
     * Scope untuk sekolah luar kota
     */
    public function scopeExternal($query)
    {
        return $query->where('is_local', false);
    }

    /**
     * Scope untuk pencarian berdasarkan NPSN atau nama
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('npsn', 'like', "%{$keyword}%")
                ->orWhere('nama', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope berdasarkan status (Negeri/Swasta)
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status_sp', $status);
    }

    // ========== ACCESSORS ==========

    /**
     * Generate alamat lengkap otomatis jika belum ada
     */
    public function getAlamatLengkapAttribute($value)
    {
        if ($value) {
            return $value;
        }

        $parts = array_filter([
            $this->alamat_jalan,
            $this->kelurahan ? "Kel. {$this->kelurahan}" : null,
            $this->kecamatan ? "Kec. {$this->kecamatan}" : null,
            $this->kota,
            $this->provinsi,
        ]);

        return implode(', ', $parts);
    }

    // ========== HELPERS ==========

    /**
     * Cari sekolah by NPSN
     */
    public static function findByNpsn(string $npsn): ?self
    {
        return static::where('npsn', $npsn)->first();
    }

    /**
     * Cek apakah NPSN ada di database lokal
     */
    public static function existsByNpsn(string $npsn): bool
    {
        return static::where('npsn', $npsn)->exists();
    }

    /**
     * Autocomplete search untuk form mutasi
     * Returns max 10 results for performance
     */
    public static function autocomplete(string $keyword, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::search($keyword)
            ->select(['id', 'npsn', 'nama', 'alamat_lengkap', 'kota', 'provinsi', 'is_local'])
            ->orderBy('nama')
            ->limit($limit)
            ->get();
    }

    /**
     * Save external school to database if not exists
     * 
     * @deprecated Use SatuanPendidikanController::maybeStoreNewSchool() instead!
     * This method uses Eloquent::create() which TRIGGERS the Observer and will
     * automatically create a user account. This is NOT the intended behavior
     * for auto-saving schools from mutation data.
     * 
     * This method should ONLY be used for admin-initiated school creation
     * where user account creation IS desired.
     * 
     * @param string $npsn NPSN sekolah
     * @param string $nama Nama sekolah
     * @param string|null $alamat Alamat lengkap
     * @param string|null $kota Kabupaten/Kota
     * @param string|null $provinsi Provinsi
     * @return self|null
     */
    public static function saveExternalSchool(
        ?string $npsn,
        ?string $nama,
        ?string $alamat = null,
        ?string $kota = null,
        ?string $provinsi = null
    ): ?self {
        $npsn = trim($npsn);

        // Validate NPSN format (Allowing alphanum for potential future cases, but standard is digit)
        // Strict 8 chars length check
        if (strlen($npsn) !== 8) {
            return null;
        }

        // CRITICAL FIX: STOP if exists.
        // Absolutely NO UPDATE allowed on existing reference data.
        $existing = static::where('npsn', $npsn)->first();

        if ($existing) {
            return $existing;
        }

        // Skip if empty nama for new records
        if (empty(trim($nama))) {
            return null;
        }

        // Only Insert if strictly NOT exists
        return static::create([
            'npsn' => $npsn,
            'nama' => trim($nama),
            'alamat_lengkap' => $alamat ? trim($alamat) : null,
            'kota' => $kota ? trim($kota) : null,
            'provinsi' => $provinsi ? trim($provinsi) : null,
            'is_local' => false, // Mark as external school
            'bentuk_pendidikan' => 'SMP',
            'status_sp' => 'Swasta', // Default to Swasta for external
        ]);
    }
}