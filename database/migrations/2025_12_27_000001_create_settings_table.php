<<<<<<< HEAD
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings - Only working days and hours
        $settings = [
            [
                'key' => 'allowed_days',
                'value' => '1,2,3,4,5,6', // Mon-Sat (0=Sun, 1=Mon, ..., 6=Sat)
                'type' => 'json',
                'label' => 'Hari yang Diperbolehkan',
                'description' => 'Hari-hari dimana pengajuan mutasi diizinkan',
            ],
            [
                'key' => 'enable_hour_restriction',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Aktifkan Pembatasan Jam',
                'description' => 'Jika aktif, pengajuan hanya bisa pada jam yang ditentukan',
            ],
            [
                'key' => 'working_hours_start',
                'value' => '08:00',
                'type' => 'string',
                'label' => 'Jam Mulai',
                'description' => 'Jam mulai operasional',
            ],
            [
                'key' => 'working_hours_end',
                'value' => '16:00',
                'type' => 'string',
                'label' => 'Jam Selesai',
                'description' => 'Jam selesai operasional',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
=======
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings - Only working days and hours
        $settings = [
            [
                'key' => 'allowed_days',
                'value' => '1,2,3,4,5,6', // Mon-Sat (0=Sun, 1=Mon, ..., 6=Sat)
                'type' => 'json',
                'label' => 'Hari yang Diperbolehkan',
                'description' => 'Hari-hari dimana pengajuan mutasi diizinkan',
            ],
            [
                'key' => 'enable_hour_restriction',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Aktifkan Pembatasan Jam',
                'description' => 'Jika aktif, pengajuan hanya bisa pada jam yang ditentukan',
            ],
            [
                'key' => 'working_hours_start',
                'value' => '08:00',
                'type' => 'string',
                'label' => 'Jam Mulai',
                'description' => 'Jam mulai operasional',
            ],
            [
                'key' => 'working_hours_end',
                'value' => '16:00',
                'type' => 'string',
                'label' => 'Jam Selesai',
                'description' => 'Jam selesai operasional',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
>>>>>>> 9466a07 (Upload project pertama kali)
