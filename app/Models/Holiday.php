<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Holiday extends Model
{
    protected $fillable = ['date', 'description'];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Check if a date is a holiday (cached for 1 hour)
     */
    public static function isHoliday(string $date): bool
    {
        return Cache::remember("holiday.{$date}", 3600, function () use ($date) {
            return static::where('date', $date)->exists();
        });
    }

    /**
     * Get holiday description for a date
     */
    public static function getDescription(string $date): ?string
    {
        return static::where('date', $date)->value('description');
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
}