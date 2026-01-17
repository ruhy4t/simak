<<<<<<< HEAD
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->softDeletes(); // Adds deleted_at
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
=======
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->softDeletes(); // Adds deleted_at
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutasi_keluar', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('mutasi_masuk', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
>>>>>>> 9466a07 (Upload project pertama kali)
