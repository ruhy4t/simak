<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create SuperAdmin
        User::create([
            'username' => 'admin',
            'password' => Hash::make('Admin123!'),
            'role' => 'SuperAdmin',
            'nama_pengguna' => 'Administrator',
            'status' => 'Aktif',
        ]);

        // Create Dinas user
        User::create([
            'username' => 'dinas',
            'password' => Hash::make('Dinas123!'),
            'role' => 'Dinas',
            'nama_pengguna' => 'Petugas Dinas',
            'status' => 'Aktif',
        ]);

        // Create sample Operator
        User::create([
            'username' => 'operator1',
            'password' => Hash::make('Operator123!'),
            'role' => 'Operator',
            'nama_pengguna' => 'Operator SDN 1 Bogor',
            'npsn' => '20253407',
            'nama_sekolah' => 'SDN 1 Bogor',
            'status' => 'Aktif',
        ]);

        $this->command->info('✅ Users created:');
        $this->command->info('   admin / Admin123! (SuperAdmin)');
        $this->command->info('   dinas / Dinas123! (Dinas)');
        $this->command->info('   operator1 / Operator123! (Operator)');

        // Seed Satuan Pendidikan (Schools)
        $this->call(SatuanPendidikanSeeder::class);
    }
}
