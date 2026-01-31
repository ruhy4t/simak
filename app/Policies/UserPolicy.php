<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can manage permissions of another user.
     * Only SuperAdmin can manage permissions.
     */
    public function managePermissions(User $currentUser, User $targetUser): bool
    {
        // Only SuperAdmin can manage permissions
        if (!$currentUser->isSuperAdmin()) {
            return false;
        }

        // Cannot modify own permissions (safety)
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $currentUser, User $targetUser): bool
    {
        // SuperAdmin and Dinas can view all users
        if ($currentUser->isSuperAdmin() || $currentUser->isDinas()) {
            return true;
        }

        // Sekolah can view users in their own school
        if ($currentUser->isSekolah()) {
            return $currentUser->npsn === $targetUser->npsn;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        // SuperAdmin can update anyone except themselves
        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        // Dinas can update Dinas and lower
        if ($currentUser->isDinas()) {
            return !$targetUser->isSuperAdmin();
        }

        // Sekolah can update Operator in their school
        if ($currentUser->isSekolah()) {
            return $targetUser->isOperator() && $currentUser->npsn === $targetUser->npsn;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $currentUser, User $targetUser): bool
    {
        // SuperAdmin and Dinas can delete (except SuperAdmin deleting other SuperAdmins)
        if ($currentUser->isSuperAdmin()) {
            return $currentUser->id !== $targetUser->id;
        }

        if ($currentUser->isDinas()) {
            return !$targetUser->isSuperAdmin() && !$targetUser->isDinas();
        }

        return false;
    }
}
