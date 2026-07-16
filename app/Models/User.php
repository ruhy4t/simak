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
        $this->failed_login_attempts++;
        $this->locked_until = null;
        $this->save();
    }

    public function recordFailedLogin(int $attempts): void
    {
        $this->failed_login_attempts = max(1, $attempts);
        $this->locked_until = null;
        $this->save();
    }

    public function resetFailedLogin(): void
    {
        $this->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();
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

    // ========== PERMISSION SYSTEM ==========

    /**
     * User's permission overrides
     */
    public function permissionOverrides()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('is_granted')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific permission
     * 
     * Logic:
     * 1. SuperAdmin always has all permissions
     * 2. Check user-level override first (grant or revoke)
     * 3. Fall back to role's default permissions
     */
    public function hasPermission(string $permissionName): bool
    {
        // SuperAdmin bypass
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Get permission model
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            return false;
        }

        // Check user-level override
        $override = $this->permissionOverrides()
            ->where('permission_id', $permission->id)
            ->first();

        if ($override) {
            return (bool) $override->pivot->is_granted;
        }

        // Fall back to role default
        return $permission->isDefaultForRole($this->role);
    }

    /**
     * Grant a permission to this user (override)
     */
    public function grantPermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            return false;
        }

        $this->permissionOverrides()->syncWithoutDetaching([
            $permission->id => ['is_granted' => true]
        ]);

        return true;
    }

    /**
     * Revoke a permission from this user (override)
     */
    public function revokePermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            return false;
        }

        $this->permissionOverrides()->syncWithoutDetaching([
            $permission->id => ['is_granted' => false]
        ]);

        return true;
    }

    /**
     * Remove permission override (revert to role default)
     */
    public function resetPermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            return false;
        }

        $this->permissionOverrides()->detach($permission->id);
        return true;
    }

    /**
     * Get all effective permissions for this user
     */
    public function getEffectivePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        // Get role defaults
        $rolePermissionIds = Permission::getDefaultsForRole($this->role);

        // Get user overrides
        $overrides = $this->permissionOverrides()->get();

        $grantedIds = collect($rolePermissionIds);

        foreach ($overrides as $override) {
            if ($override->pivot->is_granted) {
                $grantedIds->push($override->id);
            } else {
                $grantedIds = $grantedIds->reject(fn($id) => $id == $override->id);
            }
        }

        return Permission::whereIn('id', $grantedIds->unique())
            ->pluck('name')
            ->toArray();
    }
}
