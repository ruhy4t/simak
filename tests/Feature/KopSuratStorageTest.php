<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KopSuratStorageTest extends TestCase
{
    public function test_letterhead_uses_b2_in_production_instead_of_vercel_local_storage(): void
    {
        $this->app['env'] = 'production';
        config()->set('filesystems.disks.b2.key', 'test-key');
        Storage::fake('b2');

        $service = app(FileUploadService::class);
        $file = UploadedFile::fake()->createWithContent('kop-sekolah.jpg', 'jpeg-content');

        $path = $service->uploadImage(
            $file,
            '12345678',
            'kop_surat_test',
            'uploads/kop_surat',
            $service->getPublicImageDisk(),
        );

        $this->assertSame('b2', $service->getPublicImageDisk());
        Storage::disk('b2')->assertExists($path);
        $this->assertSame('uploads/kop_surat/12345678_kop_surat_test.jpg', $path);
    }

    public function test_letterhead_uses_public_disk_outside_production(): void
    {
        Storage::fake('public');

        $service = app(FileUploadService::class);
        $file = UploadedFile::fake()->createWithContent('kop-sekolah.jpeg', 'jpeg-content');

        $path = $service->uploadImage(
            $file,
            '12345678',
            'kop_surat_test',
            'uploads/kop_surat',
            $service->getPublicImageDisk(),
        );

        $this->assertSame('public', $service->getPublicImageDisk());
        Storage::disk('public')->assertExists($path);
    }
}
