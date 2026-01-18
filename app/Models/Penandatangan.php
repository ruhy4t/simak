<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penandatangan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'penandatangan';

    protected $fillable = [
        'nama',
        'nip',
        'jabatan',
        'pangkat_golongan',
        'is_active',
        'alasan_penghapusan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
