<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';
    public $timestamps = true;

    protected $fillable = [
        'event_type',
        'username',
        'ip_address',
        'details',
    ];

    // Static helper for easy logging
    public static function log(string $eventType, ?string $username, ?string $details = null): void
    {
        try {
            self::create([
                'event_type' => $eventType,
                'username' => $username ?? 'System', // Fallback for null username
                'ip_address' => request()->ip(),
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            // Silently fail but log to system error log so the app doesn't crash
            \Illuminate\Support\Facades\Log::error("AuditLog Failed: " . $e->getMessage());
        }
    }
}