<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_role_helpers_identify_user_roles(): void
    {
        $superAdmin = new User(['role' => 'SuperAdmin']);
        $dinas = new User(['role' => 'Dinas']);
        $sekolah = new User(['role' => 'Sekolah']);
        $operator = new User(['role' => 'Operator']);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($dinas->isDinas());
        $this->assertTrue($sekolah->isSekolah());
        $this->assertTrue($operator->isOperator());
    }

    public function test_access_to_npsn_matches_role_scope(): void
    {
        $dinas = new User(['role' => 'Dinas']);
        $sekolah = new User(['role' => 'Sekolah', 'npsn' => '12345678']);

        $this->assertTrue($dinas->canAccessNpsn('87654321'));
        $this->assertTrue($sekolah->canAccessNpsn('12345678'));
        $this->assertFalse($sekolah->canAccessNpsn('87654321'));
    }
}
