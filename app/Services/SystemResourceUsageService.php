<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemResourceUsageService
{
    public function database(): array
    {
        $limit = max(1, (int) config('system_status.database_limit_bytes'));
        $bytes = 0;
        $error = null;

        try {
            $database = (string) config('database.connections.mysql.database');
            $result = $this->databaseDiskUsage($database);
            $bytes = (int) ($result[0]->bytes ?? 0);
        } catch (Throwable $exception) {
            report($exception);
            $error = 'Penggunaan database tidak dapat dibaca.';
        }

        return $this->usage($bytes, $limit) + [
            'source' => 'Aiven MySQL',
            'error' => $error,
        ];
    }

    private function databaseDiskUsage(string $database): array
    {
        try {
            return DB::select(
                'SELECT COALESCE(SUM(tablespaces.FILE_SIZE), 0) AS bytes
                 FROM information_schema.INNODB_TABLESPACES AS tablespaces
                 INNER JOIN information_schema.TABLES AS tables_info
                    ON tablespaces.NAME = CONCAT(tables_info.TABLE_SCHEMA, \'/\', tables_info.TABLE_NAME)
                 WHERE tables_info.TABLE_SCHEMA = ?',
                [$database]
            );
        } catch (Throwable) {
            // Some managed MySQL roles cannot read INNODB_TABLESPACES.
            return DB::select(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS bytes
                 FROM information_schema.TABLES
                 WHERE table_schema = ?',
                [$database]
            );
        }
    }

    public function backblaze(): array
    {
        $limit = max(1, (int) config('system_status.b2_limit_bytes'));
        $bytes = 0;
        $count = 0;
        $error = null;

        if (! config('filesystems.disks.b2.key') || ! config('filesystems.disks.b2.bucket')) {
            return $this->usage($bytes, $limit) + [
                'count' => $count,
                'source' => 'Backblaze B2',
                'error' => 'Konfigurasi Backblaze B2 belum lengkap.',
            ];
        }

        try {
            $disk = Storage::disk('b2');

            // Flysystem streams every S3/B2 result page and includes each
            // object's size, avoiding one metadata request per file.
            foreach ($disk->listContents('', true) as $item) {
                if (! $item->isFile()) {
                    continue;
                }

                $count++;
                $bytes += (int) ($item->fileSize() ?? $disk->size($item->path()));
            }
        } catch (Throwable $exception) {
            report($exception);
            $error = 'Metadata bucket Backblaze B2 tidak dapat dibaca.';
        }

        return $this->usage($bytes, $limit) + [
            'count' => $count,
            'source' => 'Backblaze B2',
            'error' => $error,
        ];
    }

    private function usage(int $bytes, int $limit): array
    {
        $remaining = max(0, $limit - $bytes);

        return [
            'size' => $bytes,
            'formatted_size' => $this->formatBytes($bytes),
            'limit' => $limit,
            'formatted_limit' => $this->formatBytes($limit),
            'percentage' => round(($bytes / $limit) * 100, 2),
            'remaining' => $remaining,
            'formatted_remaining' => $this->formatBytes($remaining),
            'checked_at' => now(),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return number_format($bytes).' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes;

        foreach ($units as $unit) {
            $value /= 1024;
            if ($value < 1024 || $unit === 'TB') {
                return number_format($value, 2).' '.$unit;
            }
        }

        return number_format($bytes).' B';
    }
}
