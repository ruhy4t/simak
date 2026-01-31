<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Roles that have this permission by default
     */
    public function roles()
    {
        return $this->belongsToMany(
            null, // No model, just role string
            'role_permissions',
            'permission_id',
            'role'
        );
    }

    /**
     * Users with this permission override
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('is_granted')
            ->withTimestamps();
    }

    // ========== SCOPES ==========

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    // ========== HELPERS ==========

    /**
     * Get all permissions grouped by their group name
     */
    public static function allGrouped()
    {
        return static::orderBy('group')->orderBy('name')->get()->groupBy('group');
    }

    /**
     * Get default permissions for a role
     */
    public static function getDefaultsForRole(string $role): array
    {
        return \DB::table('role_permissions')
            ->where('role', $role)
            ->pluck('permission_id')
            ->toArray();
    }

    /**
     * Check if a role has this permission by default
     */
    public function isDefaultForRole(string $role): bool
    {
        return \DB::table('role_permissions')
            ->where('role', $role)
            ->where('permission_id', $this->id)
            ->exists();
    }
}
