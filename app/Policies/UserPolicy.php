<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any data sources.
     */
    public function viewAny(User $user): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can view the data source.
     */
    public function view(User $user, User $userSubject): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can create data sources.
     */
    public function create(User $user): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can update the data source.
     */
    public function update(User $user, User $userSubject): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can delete the data source.
     */
    public function delete(User $user, User $userSubject): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can restore the data source.
     */
    public function restore(User $user, User $userSubject): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can permanently delete the data source.
     */
    public function forceDelete(User $user, User $userSubject): bool
    {
        return $user->role_id > 1;
    }
}
