<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $role
 * @property string|null $nama_pengguna
 * @property string|null $no_kontak
 * @property string|null $telegram_chat_id
 * @property string|null $npsn
 * @property string|null $nama_sekolah
 * @property string|null $alamat
 * @property string $status
 * @property bool $is_active
 * @property int $failed_login_attempts
 * @property bool $must_change_password
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
        'nama_pengguna',
        'no_kontak',
        'telegram_chat_id',
        'npsn',
        'nama_sekolah',
        'alamat',
        'status',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'must_change_password' => 'boolean',
    ];

    // ========== ACCESSORS & MUTATORS ==========

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'Aktif';
    }

    // Mutator removed to allow direct assignment to is_active column in Controllers
    // public function setIsActiveAttribute($value) ...

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByNpsn($query, $npsn)
    {
        return $query->where('npsn', $npsn);
    }

    // ========== RELATIONSHIPS ==========

    public function mutasiKeluar()
    {
        return $this->hasMany(MutasiKeluar::class, 'pengusul', 'username');
    }

    public function sekolah()
    {
        return $this->belongsTo(SatuanPendidikan::class, 'npsn', 'npsn');
    }

    // ========== HELPERS ==========

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function incrementFailedLogin(): void
    {
        // Reset counter if last update was more than 15 minutes ago
        // This ensures the "5 attempts" window resets automatically
        if ($this->updated_at && now()->diffInMinutes($this->updated_at) > 15) {
            $this->failed_login_attempts = 1;
        } else {
            $this->failed_login_attempts++;
        }

        if ($this->failed_login_attempts >= 5) {
            $this->locked_until = now()->addMinutes(15);
        }

        $this->save();
    }

    public function resetFailedLogin(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SuperAdmin';
    }

    public function isDinas(): bool
    {
        return $this->role === 'Dinas';
    }

    public function isSekolah(): bool
    {
        return $this->role === 'Sekolah';
    }

    public function isOperator(): bool
    {
        return $this->role === 'Operator';
    }

    public function canAccessNpsn(?string $npsn): bool
    {
        if ($this->isSuperAdmin() || $this->isDinas()) {
            return true;
        }
        return $this->npsn === $npsn;
    }
}