<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    // The audit table uses a database-generated created_at value and has no
    // updated_at column. Disabling Eloquent timestamps prevents every audit
    // insert from failing and being written to the application error log.
    public $timestamps = false;

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
                'username' => mb_substr($username ?? 'System', 0, 50),
                'ip_address' => request()->ip(),
                'details' => $details,
            ]);
        } catch (\Exception $e) {
            // Silently fail but log to system error log so the app doesn't crash
            \Illuminate\Support\Facades\Log::error("AuditLog Failed: " . $e->getMessage());
        }
    }
}
