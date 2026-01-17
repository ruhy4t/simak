<<<<<<< HEAD
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiMasukSiswa extends Model
{
    use HasFactory;

    protected $table = 'mutasi_masuk_siswa';

    protected $fillable = [
        'id_mutasi',
        'nama_murid',
        'nisn',
        'nis',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'kelas',
        'tanggal_diterima',
        'npsn_sekolah_asal',
        'nama_sekolah_asal',
        'alamat_sekolah_asal',
        'provinsi_asal',
        'kabupaten_kota_asal',
        'npsn_ditemukan',
        'asal_luar_negeri',
        // Files
        'file_surat_pindah',
        'file_surat_dapodik',
        'file_rekomendasi',
        'file_ijazah_sd',
        'file_rapor',
        'file_surat_penyaluran',
        'file_rekam_didik',
        // Verifications
        'verif_surat_pindah',
        'verif_surat_dapodik',
        'verif_rekomendasi',
        'verif_ijazah_sd',
        'verif_rapor',
        'verif_surat_penyaluran',
        'verif_rekam_didik',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_diterima' => 'date',
        'npsn_ditemukan' => 'boolean',
        'asal_luar_negeri' => 'boolean',
    ];

    public function mutasi()
    {
        return $this->belongsTo(MutasiMasuk::class, 'id_mutasi', 'id_mutasi');
    }
}
=======
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiMasukSiswa extends Model
{
    use HasFactory;

    protected $table = 'mutasi_masuk_siswa';

    protected $fillable = [
        'id_mutasi',
        'nama_murid',
        'nisn',
        'nis',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'kelas',
        'tanggal_diterima',
        'npsn_sekolah_asal',
        'nama_sekolah_asal',
        'alamat_sekolah_asal',
        'provinsi_asal',
        'kabupaten_kota_asal',
        'npsn_ditemukan',
        'asal_luar_negeri',
        // Files
        'file_surat_pindah',
        'file_surat_dapodik',
        'file_rekomendasi',
        'file_ijazah_sd',
        'file_rapor',
        'file_surat_penyaluran',
        'file_rekam_didik',
        // Verifications
        'verif_surat_pindah',
        'verif_surat_dapodik',
        'verif_rekomendasi',
        'verif_ijazah_sd',
        'verif_rapor',
        'verif_surat_penyaluran',
        'verif_rekam_didik',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_diterima' => 'date',
        'npsn_ditemukan' => 'boolean',
        'asal_luar_negeri' => 'boolean',
    ];

    public function mutasi()
    {
        return $this->belongsTo(MutasiMasuk::class, 'id_mutasi', 'id_mutasi');
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
