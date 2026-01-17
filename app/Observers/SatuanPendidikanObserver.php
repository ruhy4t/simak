<<<<<<< HEAD
<?php

namespace App\Observers;

use App\Models\SatuanPendidikan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SatuanPendidikanObserver
{
    /**
     * Handle the SatuanPendidikan "created" event.
     * Automatically create a User account when a new SatuanPendidikan is added.
     */
    public function created(SatuanPendidikan $satpen): void
    {
        // Check if user with this NPSN already exists
        if (User::where('username', $satpen->npsn)->exists()) {
            return;
        }

        // Check if NPSN exists
        if (empty($satpen->npsn)) {
            return;
        }

        // Format alamat lengkap
        $alamatLengkap = $satpen->alamat_lengkap ?? sprintf(
            '%s, Kel. %s, Kec. %s, %s, %s',
            $satpen->alamat_jalan ?? '',
            $satpen->kelurahan ?? '',
            $satpen->kecamatan ?? '',
            $satpen->kota ?? 'Kota Bogor',
            $satpen->provinsi ?? 'Jawa Barat'
        );

        // Create user with:
        // Username: NPSN
        // Password: NPSN + "Fa*"
        User::create([
            'username' => $satpen->npsn,
            'password' => Hash::make($satpen->npsn . 'Fa*'),
            'role' => 'Sekolah',
            'nama_pengguna' => 'Operator ' . $satpen->nama,
            'npsn' => $satpen->npsn,
            'nama_sekolah' => $satpen->nama,
            'alamat' => $alamatLengkap,
            'status' => 'Aktif',
        ]);
    }

    /**
     * Handle the SatuanPendidikan "updated" event.
     * Sync User data when SatuanPendidikan is updated.
     */
    public function updated(SatuanPendidikan $satpen): void
    {
        $user = User::where('npsn', $satpen->npsn)->first();

        if ($user) {
            // Format alamat lengkap
            $alamatLengkap = $satpen->alamat_lengkap ?? sprintf(
                '%s, Kel. %s, Kec. %s, %s, %s',
                $satpen->alamat_jalan ?? '',
                $satpen->kelurahan ?? '',
                $satpen->kecamatan ?? '',
                $satpen->kota ?? 'Kota Bogor',
                $satpen->provinsi ?? 'Jawa Barat'
            );

            $user->update([
                'nama_sekolah' => $satpen->nama,
                'alamat' => $alamatLengkap,
            ]);
        }
    }

    /**
     * Handle the SatuanPendidikan "deleted" event.
     * Note: We don't delete the user to preserve audit trail.
     * Instead, we deactivate the user.
     */
    public function deleted(SatuanPendidikan $satpen): void
    {
        $user = User::where('npsn', $satpen->npsn)->first();

        if ($user) {
            $user->update([
                'status' => 'Non Aktif',
            ]);
        }
    }
}
=======
<?php

namespace App\Observers;

use App\Models\SatuanPendidikan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SatuanPendidikanObserver
{
    /**
     * Handle the SatuanPendidikan "created" event.
     * Automatically create a User account when a new SatuanPendidikan is added.
     */
    public function created(SatuanPendidikan $satpen): void
    {
        // Check if user with this NPSN already exists
        if (User::where('username', $satpen->npsn)->exists()) {
            return;
        }

        // Check if NPSN exists
        if (empty($satpen->npsn)) {
            return;
        }

        // Format alamat lengkap
        $alamatLengkap = $satpen->alamat_lengkap ?? sprintf(
            '%s, Kel. %s, Kec. %s, %s, %s',
            $satpen->alamat_jalan ?? '',
            $satpen->kelurahan ?? '',
            $satpen->kecamatan ?? '',
            $satpen->kota ?? 'Kota Bogor',
            $satpen->provinsi ?? 'Jawa Barat'
        );

        // Create user with:
        // Username: NPSN
        // Password: NPSN + "Fa*"
        User::create([
            'username' => $satpen->npsn,
            'password' => Hash::make($satpen->npsn . 'Fa*'),
            'role' => 'Sekolah',
            'nama_pengguna' => 'Operator ' . $satpen->nama,
            'npsn' => $satpen->npsn,
            'nama_sekolah' => $satpen->nama,
            'alamat' => $alamatLengkap,
            'status' => 'Aktif',
        ]);
    }

    /**
     * Handle the SatuanPendidikan "updated" event.
     * Sync User data when SatuanPendidikan is updated.
     */
    public function updated(SatuanPendidikan $satpen): void
    {
        $user = User::where('npsn', $satpen->npsn)->first();

        if ($user) {
            // Format alamat lengkap
            $alamatLengkap = $satpen->alamat_lengkap ?? sprintf(
                '%s, Kel. %s, Kec. %s, %s, %s',
                $satpen->alamat_jalan ?? '',
                $satpen->kelurahan ?? '',
                $satpen->kecamatan ?? '',
                $satpen->kota ?? 'Kota Bogor',
                $satpen->provinsi ?? 'Jawa Barat'
            );

            $user->update([
                'nama_sekolah' => $satpen->nama,
                'alamat' => $alamatLengkap,
            ]);
        }
    }

    /**
     * Handle the SatuanPendidikan "deleted" event.
     * Note: We don't delete the user to preserve audit trail.
     * Instead, we deactivate the user.
     */
    public function deleted(SatuanPendidikan $satpen): void
    {
        $user = User::where('npsn', $satpen->npsn)->first();

        if ($user) {
            $user->update([
                'status' => 'Non Aktif',
            ]);
        }
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
