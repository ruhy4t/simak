<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'Akses Dashboard', 'group' => 'Dashboard'],

            // Mutasi Masuk
            ['name' => 'mutasi_masuk.view', 'display_name' => 'Lihat Mutasi Masuk', 'group' => 'Mutasi Masuk'],
            ['name' => 'mutasi_masuk.create', 'display_name' => 'Buat Pengajuan Mutasi Masuk', 'group' => 'Mutasi Masuk'],
            ['name' => 'mutasi_masuk.verify', 'display_name' => 'Verifikasi Mutasi Masuk', 'group' => 'Mutasi Masuk'],

            // Mutasi Keluar
            ['name' => 'mutasi_keluar.view', 'display_name' => 'Lihat Mutasi Keluar', 'group' => 'Mutasi Keluar'],
            ['name' => 'mutasi_keluar.create', 'display_name' => 'Buat Pengajuan Mutasi Keluar', 'group' => 'Mutasi Keluar'],
            ['name' => 'mutasi_keluar.verify', 'display_name' => 'Verifikasi Mutasi Keluar', 'group' => 'Mutasi Keluar'],

            // Laporan
            ['name' => 'laporan.view', 'display_name' => 'Lihat Laporan', 'group' => 'Laporan'],
            ['name' => 'laporan.export', 'display_name' => 'Export Laporan (Excel/PDF)', 'group' => 'Laporan'],
            ['name' => 'laporan.print', 'display_name' => 'Cetak Laporan Komprehensif', 'group' => 'Laporan'],

            // Pengguna
            ['name' => 'pengguna.view', 'display_name' => 'Lihat Daftar Pengguna', 'group' => 'Pengguna'],
            ['name' => 'pengguna.create', 'display_name' => 'Tambah Pengguna', 'group' => 'Pengguna'],
            ['name' => 'pengguna.edit', 'display_name' => 'Edit Pengguna', 'group' => 'Pengguna'],
            ['name' => 'pengguna.delete', 'display_name' => 'Hapus Pengguna', 'group' => 'Pengguna'],
            ['name' => 'pengguna.permissions', 'display_name' => 'Kelola Permission User', 'group' => 'Pengguna'],

            // Sekolah
            ['name' => 'sekolah.view', 'display_name' => 'Lihat Data Sekolah', 'group' => 'Sekolah'],
            ['name' => 'sekolah.edit', 'display_name' => 'Edit Profil Sekolah', 'group' => 'Sekolah'],

            // Admin
            ['name' => 'admin.audit_log', 'display_name' => 'Lihat Audit Log', 'group' => 'Admin'],
            ['name' => 'admin.settings', 'display_name' => 'Kelola Pengaturan Sistem', 'group' => 'Admin'],
        ];

        // Insert permissions
        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }

        // Define default permissions per role
        $roleDefaults = [
            'SuperAdmin' => [
                // SuperAdmin has all permissions (handled in code via bypass)
            ],
            'Dinas' => [
                'dashboard.view',
                'mutasi_masuk.view',
                'mutasi_masuk.verify',
                'mutasi_keluar.view',
                'mutasi_keluar.verify',
                'laporan.view',
                'laporan.export',
                'laporan.print',
                'pengguna.view',
                'pengguna.create',
                'pengguna.edit',
                'pengguna.delete',
                'sekolah.view',
                'admin.audit_log',
            ],
            'Sekolah' => [
                'dashboard.view',
                'mutasi_masuk.view',
                'mutasi_masuk.create',
                'mutasi_keluar.view',
                'mutasi_keluar.create',
                'laporan.view',
                'laporan.export',
                'laporan.print',
                'pengguna.view',
                'pengguna.create',
                'pengguna.edit',
                'sekolah.view',
                'sekolah.edit',
            ],
            'Operator' => [
                'dashboard.view',
                'mutasi_masuk.view',
                'mutasi_masuk.create',
                'mutasi_keluar.view',
                'mutasi_keluar.create',
                'laporan.view',
                'sekolah.view',
            ],
        ];

        // Clear existing role permissions
        DB::table('role_permissions')->truncate();

        // Insert role defaults
        foreach ($roleDefaults as $role => $permissionNames) {
            foreach ($permissionNames as $permName) {
                $permission = Permission::where('name', $permName)->first();
                if ($permission) {
                    DB::table('role_permissions')->insert([
                        'role' => $role,
                        'permission_id' => $permission->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Permissions seeded successfully!');
        $this->command->info('Total permissions: ' . Permission::count());
    }
}
