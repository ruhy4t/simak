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
        // 1. Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // e.g. 'laporan.view'
            $table->string('display_name', 150);   // e.g. 'Lihat Laporan'
            $table->string('group', 50);           // e.g. 'Laporan'
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });

        // 2. Role Permissions (Default permissions per role)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50); // e.g. 'Dinas', 'Sekolah'
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role', 'permission_id']);
            $table->index('role');
        });

        // 3. User Permissions (Override per user)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->boolean('is_granted')->default(true); // true = grant, false = revoke
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
