<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RuntimePruner
{
    public function __construct(private readonly TemporaryUploadService $temporaryUploads)
    {
    }

    /** @return array{cache:int, cache_locks:int, temporary_uploads:int} */
    public function prune(): array
    {
        $now = now()->timestamp;
        $counts = [
            'cache' => $this->pruneDatabaseExpiry('cache', $now),
            'cache_locks' => $this->pruneDatabaseExpiry('cache_locks', $now),
            'temporary_uploads' => $this->pruneTemporaryUploads($now),
        ];

        Log::channel('metrics')->info('runtime.pruned', $counts);

        return $counts;
    }

    private function pruneDatabaseExpiry(string $table, int $now): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->where('expiration', '<=', $now)->delete();
    }

    private function pruneTemporaryUploads(int $now): int
    {
        $disk = $this->temporaryUploads->disk();
        $storage = Storage::disk($disk);
        $cutoff = $now - ((int) config('uploads.temporary_ttl_hours') * 3600);
        $deleted = 0;

        try {
            foreach ($storage->allFiles('temp_uploads') as $path) {
                try {
                    if ((int) $storage->lastModified($path) < $cutoff && $storage->delete($path)) {
                        $deleted++;
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Unable to inspect temporary upload during cleanup.', [
                        'path' => $path,
                        'exception_class' => $exception::class,
                    ]);
                }
            }
        } catch (\Throwable $exception) {
            Log::error('Temporary upload cleanup failed.', [
                'disk' => $disk,
                'exception_class' => $exception::class,
            ]);
        }

        return $deleted;
    }
}
