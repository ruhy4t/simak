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
    ];

    protected $casts = [
        'grade' => 'integer',
        'male_count' => 'integer',
        'female_count' => 'integer',
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
}
