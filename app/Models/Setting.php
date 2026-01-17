<<<<<<< HEAD
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'label', 'description'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return static::find($key);
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = static::find($key);
        if (!$setting)
            return false;

        if (is_bool($value))
            $value = $value ? '1' : '0';
        if (is_array($value))
            $value = implode(',', $value);

        $setting->value = $value;
        $setting->save();
        Cache::forget("setting.{$key}");

        return true;
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null)
            return null;

        return match ($type) {
            'boolean' => (bool) (int) $value,
            'integer' => (int) $value,
            'json' => $value ? explode(',', $value) : [],
            default => $value,
        };
    }

    /**
     * Get allowed days as array of integers
     */
    public static function getAllowedDays(): array
    {
        $days = self::get('allowed_days', [1, 2, 3, 4, 5, 6]);
        return is_array($days) ? array_map('intval', $days) : [];
    }

    /**
     * Check if hour restriction is enabled
     */
    public static function isHourRestrictionEnabled(): bool
    {
        return self::get('enable_hour_restriction', false);
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        foreach (static::all() as $setting) {
            Cache::forget("setting.{$setting->key}");
        }
    }
}
=======
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'label', 'description'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return static::find($key);
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value): bool
    {
        $setting = static::find($key);
        if (!$setting)
            return false;

        if (is_bool($value))
            $value = $value ? '1' : '0';
        if (is_array($value))
            $value = implode(',', $value);

        $setting->value = $value;
        $setting->save();
        Cache::forget("setting.{$key}");

        return true;
    }

    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null)
            return null;

        return match ($type) {
            'boolean' => (bool) (int) $value,
            'integer' => (int) $value,
            'json' => $value ? explode(',', $value) : [],
            default => $value,
        };
    }

    /**
     * Get allowed days as array of integers
     */
    public static function getAllowedDays(): array
    {
        $days = self::get('allowed_days', [1, 2, 3, 4, 5, 6]);
        return is_array($days) ? array_map('intval', $days) : [];
    }

    /**
     * Check if hour restriction is enabled
     */
    public static function isHourRestrictionEnabled(): bool
    {
        return self::get('enable_hour_restriction', false);
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        foreach (static::all() as $setting) {
            Cache::forget("setting.{$setting->key}");
        }
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
