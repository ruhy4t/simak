<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
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
            $table->string('npsn')->nullable();
            $table->string('status')->default('Aktif');
            $table->boolean('must_change_password')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Auth::logout();
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_first_login_accepts_default_password_and_clears_requirement(): void
    {
        $user = $this->createUser('20200001Fa*', false);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
        $this->assertTrue($user->fresh()->must_change_password);

        $this->changePassword($user, '20200001Fa*')->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_reset_password_accepts_latest_temporary_password(): void
    {
        $user = $this->createUser('OldPassword123!');
        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make('Temporary45@'),
            'must_change_password' => true,
        ]);

        $this->changePassword($user->fresh(), 'Temporary45@')
            ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_password_before_reset_is_rejected_without_changing_password(): void
    {
        $user = $this->createUser('Temporary45@');
        $this->changePassword($user, 'OldPassword123!')->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Temporary45@', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_confirmation_must_match_before_password_changes(): void
    {
        $user = $this->createUser('Temporary45@');
        $this->actingAs($user)->post('/password/update', [
            'current_password' => 'Temporary45@',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Temporary45@', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    private function createUser(string $password, bool $mustChangePassword = true): User
    {
        return User::create([
            'username' => 'school',
            'password' => Hash::make($password),
            'role' => 'Sekolah',
            'npsn' => '20200001',
            'must_change_password' => $mustChangePassword,
        ]);
    }

    private function changePassword(User $user, string $currentPassword): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post('/password/update', [
            'current_password' => $currentPassword,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
    }
}
