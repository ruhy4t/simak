<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username');
            $table->string('password');
            $table->string('role');
            $table->string('nama_pengguna');
            $table->string('npsn', 8)->nullable();
            $table->string('status')->default('Aktif');
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['username', 'npsn']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type');
            $table->string('username');
            $table->string('ip_address')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        DB::table('users')->insert([
            'username' => 'victim',
            'password' => Hash::make('correct-password'),
            'role' => 'Operator',
            'nama_pengguna' => 'Victim',
            'npsn' => '20200001',
            'status' => 'Aktif',
            'failed_login_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Auth::logout();
        RateLimiter::clear('login_credential:' . hash('sha256', 'operator:victim@20200001|10.0.0.1'));
        RateLimiter::clear('login_ip:' . hash('sha256', '10.0.0.1'));
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_failed_attempts_from_one_ip_do_not_lock_the_account_for_another_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->post('/login', [
                'username' => 'victim',
                'login_role' => 'operator',
                'npsn' => '20200001',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('username');
        }

        $this->assertDatabaseHas('users', [
            'username' => 'victim',
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->post('/login', [
            'username' => 'victim',
            'login_role' => 'operator',
            'npsn' => '20200001',
            'password' => 'correct-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_legacy_database_lock_does_not_block_a_valid_password(): void
    {
        DB::table('users')->where('username', 'victim')->update([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addHour(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->post('/login', [
            'username' => 'victim',
            'login_role' => 'operator',
            'npsn' => '20200001',
            'password' => 'correct-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'victim',
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    public function test_login_does_not_create_persistent_login_or_username_cookies(): void
    {
        $recallerName = Auth::guard()->getRecallerName();

        $response = $this->post('/login', [
            'username' => 'victim',
            'login_role' => 'operator',
            'npsn' => '20200001',
            'password' => 'correct-password',
            'remember_credentials' => '1',
        ]);

        $response
            ->assertRedirect('/dashboard')
            ->assertCookieExpired('remembered_username')
            ->assertCookieMissing($recallerName);

        $this->assertAuthenticated();
        $this->assertNull(DB::table('users')->where('username', 'victim')->value('remember_token'));
    }

    public function test_invalid_password_hash_is_rejected_without_server_error(): void
    {
        DB::table('users')->where('username', 'victim')->update([
            'password' => 'not-a-password-hash',
        ]);

        $this->post('/login', [
            'username' => 'victim',
            'login_role' => 'operator',
            'npsn' => '20200001',
            'password' => 'correct-password',
        ])->assertRedirect()->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'LOGIN_INVALID_PASSWORD_HASH',
            'username' => 'operator:victim@20200001',
        ]);
    }

    public function test_duplicate_operator_username_is_resolved_by_npsn(): void
    {
        DB::table('users')->where('username', 'victim')->update(['npsn' => '20200001']);
        DB::table('users')->insert([
            'username' => 'victim',
            'password' => Hash::make('second-password'),
            'role' => 'Operator',
            'nama_pengguna' => 'Second Operator',
            'npsn' => '20200002',
            'status' => 'Aktif',
            'failed_login_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'username' => 'victim',
            'login_role' => 'operator',
            'npsn' => '20200002',
            'password' => 'second-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(\App\Models\User::where('npsn', '20200002')->first());
    }

    public function test_duplicate_operator_username_requires_npsn(): void
    {
        DB::table('users')->where('username', 'victim')->update(['npsn' => '20200001']);
        DB::table('users')->insert([
            'username' => 'victim',
            'password' => Hash::make('second-password'),
            'role' => 'Operator',
            'nama_pengguna' => 'Second Operator',
            'npsn' => '20200002',
            'status' => 'Aktif',
            'failed_login_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'username' => 'victim',
            'login_role' => 'operator',
            'password' => 'second-password',
        ])->assertSessionHasErrors('npsn');

        $this->assertGuest();
    }

    public function test_selected_role_must_match_the_account_role(): void
    {
        $this->post('/login', [
            'username' => 'victim',
            'login_role' => 'sekolah',
            'npsn' => '20200001',
            'password' => 'correct-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_dinas_admin_login_does_not_require_npsn(): void
    {
        DB::table('users')->insert([
            'username' => 'staff',
            'password' => Hash::make('staff-password'),
            'role' => 'Dinas',
            'nama_pengguna' => 'Dinas Staff',
            'npsn' => null,
            'status' => 'Aktif',
            'failed_login_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'username' => 'staff',
            'login_role' => 'admin',
            'password' => 'staff-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs(\App\Models\User::where('username', 'staff')->first());
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'LOGIN_SUCCESS',
            'username' => 'admin:staff',
        ]);
    }

    public function test_login_page_offers_all_account_types(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Dinas / Admin')
            ->assertSee('Sekolah')
            ->assertSee('Operator')
            ->assertSee('NPSN wajib diisi sesuai sekolah akun Anda.');
    }
}
