<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TemporaryUploadService
{
    public function disk(): string
    {
        return config('filesystems.disks.b2.key') && app()->environment('production') ? 'b2' : 'local';
    }

    public function createPath(string $originalFilename, int|string $ownerId): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (! array_key_exists($extension, config('uploads.allowed_mime_by_extension', []))) {
            throw ValidationException::withMessages(['file' => 'Format file tidak didukung.']);
        }

        return sprintf(
            'temp_uploads/%s/temp_%s_%s.%s',
            $ownerId,
            now()->format('Ymd_His'),
            Str::random(20),
            $extension,
        );
    }

    /**
     * @param list<string>|null $allowedMimes
     * @return array{path:string,disk:string,owner_id:string,extension:string,mime_type:string,size:int,last_modified:int}
     */
    public function validateOwned(
        string $path,
        int|string|null $expectedOwnerId = null,
        ?array $allowedMimes = null,
    ): array {
        $normalized = str_replace('\\', '/', trim($path));
        if (! preg_match('#^temp_uploads/([1-9][0-9]*)/temp_[A-Za-z0-9_-]+\.([A-Za-z0-9]+)$#', $normalized, $matches)) {
            throw ValidationException::withMessages(['file' => 'Path upload sementara tidak valid.']);
        }

        $ownerId = (string) $matches[1];
        $expectedOwnerId ??= auth()->id();
        if ($expectedOwnerId === null || ! hash_equals((string) $expectedOwnerId, $ownerId)) {
            throw ValidationException::withMessages(['file' => 'Upload sementara bukan milik pengguna ini.']);
        }

        $extension = strtolower($matches[2]);
        $mimeMap = config('uploads.allowed_mime_by_extension', []);
        if (! isset($mimeMap[$extension])) {
            throw ValidationException::withMessages(['file' => 'Ekstensi upload sementara tidak diizinkan.']);
        }

        $disk = $this->disk();
        $storage = Storage::disk($disk);
        if (! $storage->exists($normalized)) {
            throw ValidationException::withMessages(['file' => 'Upload sementara tidak ditemukan atau sudah kedaluwarsa.']);
        }

        $size = (int) $storage->size($normalized);
        if ($size < 1 || $size > (int) config('uploads.temporary_max_bytes')) {
            throw ValidationException::withMessages(['file' => 'Ukuran upload sementara tidak valid atau melebihi 2 MB.']);
        }

        $detector = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = strtolower((string) $detector->buffer($storage->get($normalized)));
        $extensionMimes = $mimeMap[$extension];
        if (! in_array($mimeType, $extensionMimes, true)
            || ($allowedMimes !== null && ! in_array($mimeType, $allowedMimes, true))) {
            throw ValidationException::withMessages(['file' => 'Tipe isi upload sementara tidak sesuai dengan ekstensi.']);
        }

        $lastModified = (int) $storage->lastModified($normalized);
        $oldestAllowed = now()->subHours((int) config('uploads.temporary_ttl_hours'))->timestamp;
        if ($lastModified < $oldestAllowed) {
            throw ValidationException::withMessages(['file' => 'Upload sementara sudah kedaluwarsa. Silakan unggah ulang.']);
        }

        return [
            'path' => $normalized,
            'disk' => $disk,
            'owner_id' => $ownerId,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $size,
            'last_modified' => $lastModified,
        ];
    }

    /** @return list<string> */
    public function pathsFromInput(mixed $input): array
    {
        if (is_string($input)) {
            return str_contains($input, 'temp_uploads/') ? [$input] : [];
        }

        if (! is_array($input)) {
            return [];
        }

        $paths = [];
        array_walk_recursive($input, function ($value) use (&$paths): void {
            if (is_string($value) && str_contains($value, 'temp_uploads/')) {
                $paths[] = $value;
            }
        });

        return array_values(array_unique($paths));
    }
}
