<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MutationStatisticsCache
{
    private const VERSION_KEY = 'mutation.statistics.version';

    public static function version(): string
    {
        try {
            return (string) Cache::rememberForever(
                self::VERSION_KEY,
                fn () => (string) Str::uuid()
            );
        } catch (\Throwable) {
            return 'cache-unavailable';
        }
    }

    public static function invalidate(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, (string) Str::uuid());
        } catch (\Throwable) {
            // Query statistik tetap dapat berjalan jika cache tidak tersedia.
        }
    }
}
