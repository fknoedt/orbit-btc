<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSignal;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserSignalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any UserSignal records.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific UserSignal.
     */
    public function view(User $user, UserSignal $userSignal): bool
    {
        if ($user->role_id === config('data.role_id.super_admin')) {
            return $userSignal->user_id === $user->id || $userSignal->user_id === config('data.system_user_id');
        }
        return $userSignal->user_id === $user->id;
    }

    /**
     * Determine whether the user can create UserSignal records.
     */
    public function create(User $user): bool
    {
        return true; // Allow all authenticated users to create signals
    }

    /**
     * Determine whether the user can update a specific UserSignal.
     */
    public function update(User $user, UserSignal $userSignal): bool
    {
        if ($user->role_id === config('data.role_id.super_admin')) {
            return $userSignal->user_id === $user->id || $userSignal->user_id === config('data.system_user_id');
        }
        return $userSignal->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete a specific UserSignal.
     */
    public function delete(User $user, UserSignal $userSignal): bool
    {
        if ($user->role_id === config('data.role_id.super_admin')) {
            return $userSignal->user_id === $user->id || $userSignal->user_id === config('data.system_user_id');
        }
        return $userSignal->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore a soft-deleted UserSignal.
     */
    public function restore(User $user, UserSignal $userSignal): bool
    {
        return $this->update($user, $userSignal);
    }

    /**
     * Determine whether the user can permanently delete a UserSignal.
     */
    public function forceDelete(User $user, UserSignal $userSignal): bool
    {
        return $this->delete($user, $userSignal);
    }
}
