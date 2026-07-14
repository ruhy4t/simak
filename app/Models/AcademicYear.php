<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name',
        'status',
        'is_active',
        'starts_on',
        'ends_on',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function studentCounts()
    {
        return $this->hasMany(SatuanPendidikanStudentCount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
