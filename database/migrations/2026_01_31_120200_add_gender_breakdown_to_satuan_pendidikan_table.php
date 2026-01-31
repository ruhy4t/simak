<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->integer('jumlah_siswa_7_l')->default(0)->after('jumlah_siswa_awal_9');
            $table->integer('jumlah_siswa_7_p')->default(0)->after('jumlah_siswa_7_l');
            $table->integer('jumlah_siswa_8_l')->default(0)->after('jumlah_siswa_7_p');
            $table->integer('jumlah_siswa_8_p')->default(0)->after('jumlah_siswa_8_l');
            $table->integer('jumlah_siswa_9_l')->default(0)->after('jumlah_siswa_8_p');
            $table->integer('jumlah_siswa_9_p')->default(0)->after('jumlah_siswa_9_l');
        });
    }

    public function down()
    {
        Schema::table('satuan_pendidikan', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_siswa_7_l',
                'jumlah_siswa_7_p',
                'jumlah_siswa_8_l',
                'jumlah_siswa_8_p',
                'jumlah_siswa_9_l',
                'jumlah_siswa_9_p'
            ]);
        });
    }
};
