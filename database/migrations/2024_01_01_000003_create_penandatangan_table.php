<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penandatangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('nip', 30)->nullable();
            $table->string('jabatan', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();
            $table->string('username', 50)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('penandatangan');
    }
};