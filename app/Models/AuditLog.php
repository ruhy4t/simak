<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';
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
        self::create([
            'event_type' => $eventType,
            'username' => $username,
            'ip_address' => request()->ip(),
            'details' => $details,
        ]);
    }
}