<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penandatangan', function (Blueprint $table) {
            $table->string('gambar_ttd', 255)->nullable()->after('pangkat_golongan');
        });
    }

    public function down(): void
    {
        Schema::table('penandatangan', function (Blueprint $table) {
            $table->dropColumn('gambar_ttd');
        });
    }
};
