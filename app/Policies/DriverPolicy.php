<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Driver;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_drivers');
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->hasPermissionTo('view_drivers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_drivers');
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->hasPermissionTo('edit_drivers');
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->hasPermissionTo('delete_drivers');
    }
}
