<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_application_uses_expected_core_configuration(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertArrayHasKey('b2', config('filesystems.disks'));
        $this->assertSame('s3', config('filesystems.disks.b2.driver'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_vercel_deployment_configuration_matches_laravel_build(): void
    {
        $vercel = json_decode(file_get_contents(base_path('vercel.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('npm run build', $vercel['buildCommand']);
        $this->assertSame('public', $vercel['outputDirectory']);
        $this->assertSame('vercel-php@0.7.4', $vercel['functions']['api/*.php']['runtime']);
    }
}
