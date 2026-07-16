<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiMasuk extends Model
{
    use HasFactory;

    protected $table = 'mutasi_masuk';
    protected $primaryKey = 'id_mutasi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_mutasi',
        'tanggal_pengajuan',
        'last_submitted_at',
        'revision_count',
        'jumlah_siswa',
        'nomor_surat',
        'tanggal_surat',
        'file_surat_permohonan',
        'npsn_sekolah_tujuan',
        'nama_sekolah_tujuan',
        'alamat_sekolah_tujuan',
        'status',
        'pengusul',
        'catatan_berkas',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'last_submitted_at' => 'datetime',
        'revision_count' => 'integer',
        'tanggal_surat' => 'date',
    ];

    // ========== SCOPES ==========

    public function scopeByNpsn($query, $npsn)
    {
        return $query->where('npsn_sekolah_tujuan', $npsn);
    }

    public function scopeByPengusul($query, $username)
    {
        return $query->where('pengusul', $username);
    }

    public function scopeLatestSubmissionFirst($query)
    {
        return $query->orderByDesc('last_submitted_at')
            ->orderByDesc('id_mutasi');
    }

    // ========== RELATIONSHIPS ==========

    public function siswa()
    {
        return $this->hasMany(MutasiMasukSiswa::class, 'id_mutasi', 'id_mutasi');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'pengusul', 'username');
    }

    // ========== HELPERS ==========

    public static function generateId(): string
    {
        return 'MM-' . time();
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'Menunggu Verifikasi Dinas' => 'bg-yellow-100 text-yellow-800',
            'Proses' => 'bg-blue-100 text-blue-800',
            'Disetujui Dinas' => 'bg-green-100 text-green-800',
            'Perlu Perbaikan' => 'bg-orange-100 text-orange-800',
            'Selesai' => 'bg-emerald-100 text-emerald-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
