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
        // Provinsi (2 digit code)
        Schema::create('ref_provinsi', function (Blueprint $table) {
            $table->char('id', 2)->primary();
            $table->string('nama');
            $table->timestamps();
        });

        // Kabupaten/Kota (4 digit code: PP.KK)
        Schema::create('ref_kabupaten', function (Blueprint $table) {
            $table->char('id', 4)->primary();
            $table->char('province_id', 2);
            $table->string('nama');
            $table->timestamps();

            $table->foreign('province_id')->references('id')->on('ref_provinsi')->onDelete('cascade');
        });

        // Kecamatan (7 digit code: PP.KK.CC)
        Schema::create('ref_kecamatan', function (Blueprint $table) {
            $table->char('id', 7)->primary();
            $table->char('regency_id', 4);
            $table->string('nama');
            $table->timestamps();

            $table->foreign('regency_id')->references('id')->on('ref_kabupaten')->onDelete('cascade');
        });

        // Kelurahan/Desa (10 digit code: PP.KK.CC.DD)
        Schema::create('ref_kelurahan', function (Blueprint $table) {
            $table->char('id', 10)->primary();
            $table->char('district_id', 7);
            $table->string('nama');
            $table->timestamps();

            $table->foreign('district_id')->references('id')->on('ref_kecamatan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_kelurahan');
        Schema::dropIfExists('ref_kecamatan');
        Schema::dropIfExists('ref_kabupaten');
        Schema::dropIfExists('ref_provinsi');
    }
};