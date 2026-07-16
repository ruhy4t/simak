<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TemporaryUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TemporaryUploadSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $user = new User(['username' => 'upload-owner', 'role' => 'Operator']);
        $user->id = 42;
        Auth::setUser($user);
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();
        parent::tearDown();
    }

    public function test_temporary_path_contains_owner_and_returns_verified_metadata(): void
    {
        $service = app(TemporaryUploadService::class);
        $path = $service->createPath('dokumen.pdf', 42);
        Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");

        $metadata = $service->validateOwned($path, 42, ['application/pdf']);

        $this->assertStringStartsWith('temp_uploads/42/', $path);
        $this->assertSame('42', $metadata['owner_id']);
        $this->assertSame('application/pdf', $metadata['mime_type']);
        $this->assertGreaterThan(0, $metadata['size']);
    }

    public function test_another_users_temporary_upload_is_rejected(): void
    {
        $service = app(TemporaryUploadService::class);
        $path = $service->createPath('dokumen.pdf', 99);
        Storage::disk('local')->put($path, "%PDF-1.4\n%%EOF");

        $this->expectException(ValidationException::class);
        $service->validateOwned($path, 42);
    }

    public function test_spoofed_extension_or_path_traversal_is_rejected(): void
    {
        $service = app(TemporaryUploadService::class);
        $path = 'temp_uploads/42/temp_safe.pdf';
        Storage::disk('local')->put($path, 'not a pdf');

        try {
            $service->validateOwned($path, 42);
            $this->fail('Spoofed MIME was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->expectException(ValidationException::class);
        $service->validateOwned('temp_uploads/42/../99/temp_stolen.pdf', 42);
    }
}
