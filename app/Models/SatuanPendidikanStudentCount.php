<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuanPendidikanStudentCount extends Model
{
    protected $fillable = [
        'satuan_pendidikan_id',
        'academic_year_id',
        'grade',
        'male_count',
        'female_count',
        'confirmed_at',
        'closing_male_count',
        'closing_female_count',
        'closing_confirmed_at',
    ];

    protected $casts = [
        'grade' => 'integer',
        'male_count' => 'integer',
        'female_count' => 'integer',
        'confirmed_at' => 'datetime',
        'closing_male_count' => 'integer',
        'closing_female_count' => 'integer',
        'closing_confirmed_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(SatuanPendidikan::class, 'satuan_pendidikan_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getTotalAttribute(): int
    {
        return $this->male_count + $this->female_count;
    }

    public function getClosingTotalAttribute(): ?int
    {
        if ($this->closing_male_count === null || $this->closing_female_count === null) {
            return null;
        }

        return $this->closing_male_count + $this->closing_female_count;
    }
}
