<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Holiday extends Model
{
    private const CACHE_PREFIX = 'holiday.details.';

    protected $fillable = ['date', 'description'];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (Holiday $holiday) {
            static::forgetDateCache($holiday->date->format('Y-m-d'));

            if ($holiday->wasChanged('date')) {
                static::forgetDateCache($holiday->getPrevious()['date'] ?? null);
            }
        });

        static::deleted(fn (Holiday $holiday) => static::forgetDateCache($holiday->date->format('Y-m-d')));
    }

    /**
     * Check if a date is a holiday (cached for 1 hour)
     */
    public static function isHoliday(string $date): bool
    {
        return self::forDate($date) !== null;
    }

    /**
     * Get holiday description for a date
     */
    public static function getDescription(string $date): ?string
    {
        return self::forDate($date)?->description;
    }

    /**
     * Get all holidays ordered by date
     */
    public static function getAllOrdered()
    {
        return static::orderBy('date', 'asc')->get();
    }

    /**
     * Get upcoming holidays (from today)
     */
    public static function getUpcoming(int $limit = 10)
    {
        return static::where('date', '>=', now()->format('Y-m-d'))
            ->orderBy('date', 'asc')
            ->limit($limit)
            ->get();
    }

    private static function forDate(string $date): ?self
    {
        return Cache::remember(self::CACHE_PREFIX . $date, 3600, function () use ($date) {
            return static::query()
                ->select(['id', 'date', 'description'])
                ->where('date', $date)
                ->first();
        });
    }

    private static function forgetDateCache(?string $date): void
    {
        if ($date) {
            Cache::forget(self::CACHE_PREFIX . $date);
            Cache::forget("holiday.{$date}");
        }
    }
}
