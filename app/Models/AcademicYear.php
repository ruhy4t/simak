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
        'student_counts_rolled_over_from_id',
        'student_counts_rolled_over_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'student_counts_rolled_over_at' => 'datetime',
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
