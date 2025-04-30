<?php

namespace App\Policies;

use App\Models\DailyPrice;
use App\Models\User;

class DailyPricePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DailyPrice $dailyPrice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DailyPrice $dailyPrice): bool
    {
        return false;
    }

    public function delete(User $user, DailyPrice $dailyPrice): bool
    {
        return false;
    }

    public function restore(User $user, DailyPrice $dailyPrice): bool
    {
        return false;
    }

    public function forceDelete(User $user, DailyPrice $dailyPrice): bool
    {
        return false;
    }
}
