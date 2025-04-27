<?php

namespace App\Policies;

use App\Models\ApiClient;
use App\Models\Request;
use App\Models\User;

class ApiClientPolicy
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
    public function view(User $user, Request $request): bool
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
    public function update(User $user, ApiClient $apiClient): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can delete the data source.
     */
    public function delete(User $user, ApiClient $apiClient): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can restore the data source.
     */
    public function restore(User $user, ApiClient $apiClient): bool
    {
        return $user->role_id > 1;
    }

    /**
     * Determine if the user can permanently delete the data source.
     */
    public function forceDelete(User $user, ApiClient $apiClient): bool
    {
        return $user->role_id > 1;
    }
}
