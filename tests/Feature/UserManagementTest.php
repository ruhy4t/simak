<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 50);
            $table->string('password');
            $table->string('role');
            $table->string('nama_pengguna', 100)->nullable();
            $table->string('no_kontak', 20)->nullable();
            $table->string('telegram_chat_id', 50)->nullable();
            $table->string('npsn', 8)->nullable();
            $table->string('nama_sekolah', 150)->nullable();
            $table->text('alamat')->nullable();
            $table->string('status')->default('Aktif');
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('is_active')->default(true);
            $table->unique(['username', 'npsn'], 'users_username_npsn_unique');
        });

        Schema::create('satuan_pendidikan', function (Blueprint $table): void {
            $table->id();
            $table->string('npsn', 8)->unique();
            $table->string('nama');
            $table->boolean('is_local')->default(true);
            $table->text('alamat_lengkap')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_school_can_create_an_operator_for_its_own_npsn(): void
    {
        $school = $this->createUser('sekolah_a', 'Sekolah', '20200001');

        $response = $this->actingAs($school)->post(route('users.store'), [
            'username' => 'operator_baru',
            'nama_pengguna' => 'Operator Baru',
            'no_kontak' => '081234567890',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'username' => 'operator_baru',
            'role' => 'Operator',
            'npsn' => '20200001',
            'nama_sekolah' => 'Sekolah a',
            'must_change_password' => 1,
        ]);
    }

    public function test_school_user_management_page_renders_the_csp_safe_modal(): void
    {
        $school = $this->createUser('sekolah_halaman', 'Sekolah', '20200005');

        $response = $this->actingAs($school)->get(route('users.manage'));

        $response->assertOk()
            ->assertSee('x-data="schoolUserManagement"', false)
            ->assertSee('@click="openAddModal"', false)
            ->assertDontSee('onclick=', false);
    }

    public function test_same_username_is_allowed_for_a_different_npsn(): void
    {
        $school = $this->createUser('sekolah_b', 'Sekolah', '20200002');
        $this->createUser('operator_bersama', 'Operator', '20200001');

        $response = $this->actingAs($school)->post(route('users.store'), [
            'username' => 'operator_bersama',
            'nama_pengguna' => 'Operator Sekolah B',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(2, DB::table('users')->where('username', 'operator_bersama')->count());
    }

    public function test_same_username_is_rejected_within_the_same_npsn(): void
    {
        $school = $this->createUser('sekolah_c', 'Sekolah', '20200003');
        $this->createUser('operator_sama', 'Operator', '20200003');

        $response = $this->actingAs($school)->post(route('users.store'), [
            'username' => 'operator_sama',
            'nama_pengguna' => 'Operator Duplikat',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('username');
        $this->assertSame(1, DB::table('users')->where('username', 'operator_sama')->count());
    }

    public function test_school_with_invalid_npsn_gets_a_clear_error(): void
    {
        $school = $this->createUser('sekolah_tanpa_npsn', 'Sekolah', null);

        $response = $this->actingAs($school)->post(route('users.store'), [
            'username' => 'operator_gagal',
            'nama_pengguna' => 'Operator Gagal',
        ]);

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['username' => 'operator_gagal']);
    }

    public function test_dinas_uses_the_canonical_school_name_when_creating_a_user(): void
    {
        $dinas = $this->createUser('dinas', 'Dinas', null);
        DB::table('satuan_pendidikan')->insert([
            'npsn' => '20200004',
            'nama' => 'SMP Negeri Empat',
            'is_local' => true,
        ]);

        $response = $this->actingAs($dinas)->post(route('dinas.users.store'), [
            'role' => 'Operator',
            'username' => 'operator_empat',
            'nama_pengguna' => 'Operator Empat',
            'npsn' => '20200004',
            'nama_sekolah' => 'Nama yang dimanipulasi',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'username' => 'operator_empat',
            'npsn' => '20200004',
            'nama_sekolah' => 'SMP Negeri Empat',
        ]);
    }

    public function test_dinas_user_management_page_renders_the_csp_safe_modal(): void
    {
        $dinas = $this->createUser('dinas_halaman', 'Dinas', null);

        $response = $this->actingAs($dinas)->get(route('dinas.users'));

        $response->assertOk()
            ->assertSee('x-data="dinasUserManagement"', false)
            ->assertSee('@input.debounce.500ms="lookupNpsn"', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('<script>', false);
    }

    public function test_dinas_user_management_page_shows_telegram_link_status(): void
    {
        $dinas = $this->createUser('dinas_telegram', 'Dinas', null);
        $this->createUser('sekolah_tertaut', 'Sekolah', '20200006');
        $this->createUser('operator_belum_tertaut', 'Operator', '20200007');
        DB::table('users')
            ->where('username', 'operator_belum_tertaut')
            ->update(['telegram_chat_id' => null]);

        $this->actingAs($dinas)
            ->get(route('dinas.users'))
            ->assertOk()
            ->assertSee('Tautan Telegram')
            ->assertSee('Tertaut')
            ->assertSee('Belum tertaut');
    }

    private function createUser(string $username, string $role, ?string $npsn): User
    {
        $id = DB::table('users')->insertGetId([
            'username' => $username,
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'nama_pengguna' => ucfirst(str_replace('_', ' ', $username)),
            'npsn' => $npsn,
            'telegram_chat_id' => '123456789',
            'nama_sekolah' => $npsn ? 'Sekolah '.substr($username, -1) : null,
            'status' => 'Aktif',
            'is_active' => true,
            'must_change_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }
}
