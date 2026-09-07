<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_masuk_siswa', function (Blueprint $table) {
            $table->string('nisn', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve students without NISN; reverting to NOT NULL would lose valid data.
    }
};
