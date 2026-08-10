<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiKeluar extends Model
{
    use HasFactory;

    protected $table = 'mutasi_keluar';
    protected $primaryKey = 'id_mutasi';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_mutasi',
        'tanggal_pengajuan',
        'last_submitted_at',
        'revision_count',
        'nama_murid',
        'nisn',
        'nis',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'kelas',
        'nama_sekolah_asal',
        'npsn_sekolah_asal',
        'alamat_sekolah_asal',
        'npsn_sekolah_tujuan',
        'nama_sekolah_tujuan',
        'alamat_sekolah_tujuan',
        'kabupaten_kota_tujuan',
        'provinsi_tujuan',
        'alasan_pindah',
        'nomor_surat_sekolah',
        'tanggal_surat_sekolah',
        'status',
        'pengusul',
        'file_suket_keluar',
        'file_suket_dapo',
        'file_suket_diterima',
        'file_rapor',
        'ceklis_surat_sekolah',
        'ceklis_berkas_dapodik',
        'ceklis_sekolah_tujuan',
        'ceklis_rapor',
        'catatan_berkas',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'last_submitted_at' => 'datetime',
        'revision_count' => 'integer',
        'tanggal_lahir' => 'date',
        'tanggal_surat_sekolah' => 'date',
        'ceklis_surat_sekolah' => 'boolean',
        'ceklis_berkas_dapodik' => 'boolean',
        'ceklis_sekolah_tujuan' => 'boolean',
        'ceklis_rapor' => 'boolean',
    ];

    // ========== SCOPES ==========

    public function scopeByNpsn($query, $npsn)
    {
        return $query->where('npsn_sekolah_asal', $npsn);
    }

    public function scopeByPengusul($query, $username)
    {
        return $query->where('pengusul', $username);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeLatestSubmissionFirst($query)
    {
        return $query->orderByDesc('last_submitted_at')
            ->orderByDesc('id_mutasi');
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('nama_murid', 'LIKE', "%{$keyword}%")
                ->orWhere('nisn', 'LIKE', "%{$keyword}%")
                ->orWhere('nama_sekolah_asal', 'LIKE', "%{$keyword}%");
        });
    }

    public function scopeByMonth($query, $yearMonth)
    {
        [$year, $month] = explode('-', $yearMonth);
        $start = CarbonImmutable::create((int) $year, (int) $month, 1)->startOfDay();

        return $query->where('tanggal_pengajuan', '>=', $start->toDateString())
            ->where('tanggal_pengajuan', '<', $start->addMonth()->toDateString());
    }

    // ========== RELATIONSHIPS ==========

    public function user()
    {
        return $this->belongsTo(User::class, 'pengusul', 'username');
    }

    // ========== HELPERS ==========

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

    public function isAllChecked(): bool
    {
        return $this->ceklis_surat_sekolah
            && $this->ceklis_berkas_dapodik
            && $this->ceklis_sekolah_tujuan
            && $this->ceklis_rapor;
    }
}
