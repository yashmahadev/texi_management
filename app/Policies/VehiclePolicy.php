<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_vehicles');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('view_vehicles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_vehicles');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('edit_vehicles');
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasPermissionTo('delete_vehicles');
    }
}
