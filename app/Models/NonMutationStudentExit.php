<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonMutationStudentExit extends Model
{
    public const REASONS = [
        'Tanpa keterangan',
        'Putus sekolah',
        'Meninggal dunia',
        'Dikeluarkan sekolah',
        'Lainnya',
    ];

    protected $fillable = [
        'satuan_pendidikan_id',
        'academic_year_id',
        'student_name',
        'nisn',
        'grade',
        'gender',
        'exit_date',
        'reason',
        'notes',
        'reported_by',
    ];

    protected $casts = [
        'grade' => 'integer',
        'exit_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(SatuanPendidikan::class, 'satuan_pendidikan_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
