<<<<<<< HEAD
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->enum('role', ['SuperAdmin', 'Dinas', 'Sekolah', 'Operator']);
            $table->string('nama_pengguna', 100);
            $table->string('no_kontak', 20)->nullable();
            $table->string('npsn', 8)->nullable()->index();
            $table->string('nama_sekolah', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status', ['Aktif', 'Non Aktif'])->default('Aktif');
            $table->timestamp('last_login_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
=======
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->enum('role', ['SuperAdmin', 'Dinas', 'Sekolah', 'Operator']);
            $table->string('nama_pengguna', 100);
            $table->string('no_kontak', 20)->nullable();
            $table->string('npsn', 8)->nullable()->index();
            $table->string('nama_sekolah', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status', ['Aktif', 'Non Aktif'])->default('Aktif');
            $table->timestamp('last_login_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
>>>>>>> 9466a07 (Upload project pertama kali)
