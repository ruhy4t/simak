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
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role');
            $table->string('nama_pengguna');
            $table->string('status')->default('Aktif');
            $table->boolean('must_change_password')->default(false);
            $table->unsignedInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type');
            $table->string('username');
            $table->string('ip_address')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'username' => 'victim',
            'password' => Hash::make('correct-password'),
            'role' => 'Operator',
            'nama_pengguna' => 'Victim',
            'status' => 'Aktif',
            'failed_login_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Auth::logout();
        RateLimiter::clear('login_credential:' . hash('sha256', 'victim|10.0.0.1'));
        RateLimiter::clear('login_ip:' . hash('sha256', '10.0.0.1'));
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_failed_attempts_from_one_ip_do_not_lock_the_account_for_another_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->post('/login', [
                'username' => 'victim',
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
}
