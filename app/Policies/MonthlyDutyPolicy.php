<?php

namespace App\Policies;

use App\Models\MonthlyDuty;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MonthlyDutyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_monthly_duties');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MonthlyDuty $monthlyDuty): bool
    {
        return $user->hasPermissionTo('view_monthly_duties');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_monthly_duties');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MonthlyDuty $monthlyDuty): bool
    {
        return $user->hasPermissionTo('edit_monthly_duties');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MonthlyDuty $monthlyDuty): bool
    {
        return $user->hasPermissionTo('delete_monthly_duties');
    }
}
