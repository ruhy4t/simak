<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * PdfService - Handles data preparation for PDF/Surat generation
 * 
 * Extracted from MutasiController and MutasiMasukController.
 * Maps database fields to template-friendly format.
 */
class PdfService
{
    /**
     * Get active penandatangan (signer)
     */
    public function getActivePenandatangan(): ?object
    {
        return DB::table('penandatangan')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Prepare data for Surat Keterangan Mutasi Keluar PDF
     * 
     * @param object $mutasi Raw mutasi_keluar record
     * @return object Mapped data for template
     */
    public function prepareMutasiKeluarData(object $mutasi): object
    {
        return (object) [
            'id_mutasi' => $mutasi->id_mutasi,
            'tanggal_pengajuan' => $mutasi->tanggal_pengajuan,
            'nomor_surat_izin' => $mutasi->nomor_surat_izin,
            'tanggal_surat_izin' => $mutasi->tanggal_surat_izin,
            'nomor_surat_keluar' => $mutasi->nomor_surat_sekolah ?? null,
            'tanggal_surat_keluar' => $mutasi->tanggal_surat_sekolah ?? null,
            'nama_siswa' => $mutasi->nama_murid,
            'nisn' => $mutasi->nisn,
            'nis' => $mutasi->nis ?? null,
            'jenis_kelamin' => $mutasi->jenis_kelamin ?? null,
            'kelas' => $mutasi->kelas,
            'jenjang' => 'SMP',
            'tempat_lahir' => $mutasi->tempat_lahir,
            'tanggal_lahir' => $mutasi->tanggal_lahir,
            'sekolah_asal' => $mutasi->nama_sekolah_asal,
            'npsn_asal' => $mutasi->npsn_sekolah_asal,
            'alamat_sekolah_asal' => $mutasi->alamat_sekolah_asal,
            'sekolah_tujuan' => $mutasi->nama_sekolah_tujuan,
            'npsn_tujuan' => $mutasi->npsn_sekolah_tujuan,
            'alamat_tujuan' => $mutasi->alamat_sekolah_tujuan,
            'kota_tujuan' => $mutasi->kabupaten_kota_tujuan,
            'provinsi_tujuan' => $mutasi->provinsi_tujuan,
            'alasan_pindah' => $mutasi->alasan_pindah,
        ];
    }

    /**
     * Prepare data for Surat Izin Mutasi Masuk PDF
     * 
     * @param object $mutasi Raw mutasi_masuk record
     * @return object Mapped data for template
     */
    public function prepareMutasiMasukData(object $mutasi): object
    {
        return (object) [
            'id_mutasi' => $mutasi->id_mutasi,
            'tanggal_pengajuan' => $mutasi->tanggal_pengajuan,
            'nomor_surat_izin' => $mutasi->nomor_surat_izin,
            'tanggal_surat_izin' => $mutasi->tanggal_surat_izin,
            'nomor_surat_permohonan' => $mutasi->nomor_surat,
            'tanggal_surat_permohonan' => $mutasi->tanggal_surat,
            'sekolah_tujuan' => $mutasi->nama_sekolah_tujuan,
            'alamat_sekolah_tujuan' => $mutasi->alamat_sekolah_tujuan,
            'kota_sekolah_tujuan' => 'Bogor',
        ];
    }

    /**
     * Get and map siswa list for Mutasi Masuk PDF
     * 
     * @param string $idMutasi
     * @return \Illuminate\Support\Collection
     */
    public function getMutasiMasukSiswaList(string $idMutasi): \Illuminate\Support\Collection
    {
        return DB::table('mutasi_masuk_siswa')
            ->where('id_mutasi', $idMutasi)
            ->get()
            ->map(function ($siswa) {
                return (object) [
                    'nama_siswa' => $siswa->nama_murid,
                    'nisn' => $siswa->nisn,
                    'jenis_kelamin' => $siswa->jenis_kelamin,
                    'tempat_lahir' => $siswa->tempat_lahir,
                    'tanggal_lahir' => $siswa->tanggal_lahir,
                    'kelas' => $siswa->kelas,
                    'sekolah_asal' => $siswa->nama_sekolah_asal,
                    'npsn_asal' => $siswa->npsn_sekolah_asal,
                    'kabupaten_kota_asal' => $siswa->kabupaten_kota_asal,
                    'provinsi_asal' => $siswa->provinsi_asal,
                    'tanggal_diterima' => $siswa->tanggal_diterima,
                ];
            });
    }

    /**
     * Check if mutasi status allows printing
     * 
     * @param string $status
     * @return bool
     */
    public function canPrint(string $status): bool
    {
        return in_array($status, ['Proses TTD', 'Selesai']);
    }
}