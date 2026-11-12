<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FileUploadService;
use App\Services\TemporaryUploadService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
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

    public function test_same_disk_promotions_copy_on_storage_without_downloading_again(): void
    {
        $pdf = 'temp_uploads/42/temp_document.pdf';
        $image = 'temp_uploads/42/temp_image.png';
        $other = 'temp_uploads/42/temp_other.pdf';
        $uploads = Mockery::mock(TemporaryUploadService::class);
        $uploads->shouldReceive('validateOwned')->once()->with($pdf, null, ['application/pdf'])
            ->andReturn(['path' => $pdf, 'disk' => 'b2']);
        $uploads->shouldReceive('validateOwned')->once()->with($image, null, ['image/jpeg', 'image/png', 'image/webp'])
            ->andReturn(['path' => $image, 'disk' => 'b2', 'extension' => 'png']);
        $uploads->shouldReceive('validateOwned')->once()->with($other, 42, ['application/pdf'])
            ->andReturn(['path' => $other, 'disk' => 'b2', 'owner_id' => '42', 'mime_type' => 'application/pdf', 'size' => 100]);

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('copy')->once()->with($pdf, 'documents/id_surat.pdf')->ordered()->andReturnTrue();
        $disk->shouldReceive('delete')->once()->with($pdf)->ordered()->andReturnTrue();
        $disk->shouldReceive('copy')->once()->with($image, 'documents/id_gambar.png')->ordered()->andReturnTrue();
        $disk->shouldReceive('delete')->once()->with($image)->ordered()->andReturnTrue();
        $disk->shouldReceive('copy')->once()->with($other, 'documents/other.pdf')->ordered()->andReturnTrue();
        $disk->shouldReceive('delete')->once()->with($other)->ordered()->andReturnTrue();
        $disk->shouldNotReceive('get');
        $disk->shouldNotReceive('put');
        Storage::shouldReceive('disk')->with('b2')->times(6)->andReturn($disk);

        $service = Mockery::mock(FileUploadService::class, [$uploads])->makePartial();
        $service->shouldReceive('getDisk')->times(3)->andReturn('b2');

        $this->assertSame('documents/id_surat.pdf', $service->uploadPdf($pdf, 'id', 'surat', 'documents'));
        $this->assertSame('documents/id_gambar.png', $service->uploadImage($image, 'id', 'gambar', 'documents'));
        $this->assertSame('documents/other.pdf', $service->promoteTemporaryUpload($other, 'documents/other.pdf', ['application/pdf'], 42));
    }

    public function test_failed_storage_copy_does_not_delete_temporary_upload(): void
    {
        $path = 'temp_uploads/42/temp_document.pdf';
        $uploads = Mockery::mock(TemporaryUploadService::class);
        $uploads->shouldReceive('validateOwned')->once()->with($path, null, ['application/pdf'])
            ->andReturn(['path' => $path, 'disk' => 'b2']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('copy')->once()->with($path, 'documents/id_surat.pdf')->andReturnFalse();
        $disk->shouldNotReceive('delete');
        Storage::shouldReceive('disk')->with('b2')->twice()->andReturn($disk);
        $service = Mockery::mock(FileUploadService::class, [$uploads])->makePartial();
        $service->shouldReceive('getDisk')->once()->andReturn('b2');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Berkas sementara gagal disalin ke penyimpanan.');
        $service->uploadPdf($path, 'id', 'surat', 'documents');
    }

    public function test_cross_disk_promotion_preserves_file_contents(): void
    {
        Storage::fake('private_docs');
        $path = app(TemporaryUploadService::class)->createPath('document.pdf', 42);
        $content = "%PDF-1.4\n%%EOF";
        Storage::disk('local')->put($path, $content);

        $result = app(FileUploadService::class)->uploadPdf($path, 'id', 'surat', 'documents');

        $this->assertSame($content, Storage::disk('private_docs')->get($result));
        Storage::disk('local')->assertMissing($path);
    }

    public function test_failed_cross_disk_write_does_not_delete_temporary_upload(): void
    {
        $path = 'temp_uploads/42/temp_document.pdf';
        $uploads = Mockery::mock(TemporaryUploadService::class);
        $uploads->shouldReceive('validateOwned')->once()->with($path, null, ['application/pdf'])
            ->andReturn(['path' => $path, 'disk' => 'local']);
        $source = Mockery::mock(FilesystemAdapter::class);
        $source->shouldReceive('get')->once()->with($path)->andReturn('%PDF-1.4');
        $source->shouldNotReceive('delete');
        $target = Mockery::mock(FilesystemAdapter::class);
        $target->shouldReceive('put')->once()->with('documents/id_surat.pdf', '%PDF-1.4')->andReturnFalse();
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($source);
        Storage::shouldReceive('disk')->once()->with('private_docs')->andReturn($target);
        $service = Mockery::mock(FileUploadService::class, [$uploads])->makePartial();
        $service->shouldReceive('getDisk')->once()->andReturn('private_docs');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Berkas sementara gagal disalin ke penyimpanan.');
        $service->uploadPdf($path, 'id', 'surat', 'documents');
    }
}
