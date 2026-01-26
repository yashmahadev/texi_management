<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\DirectBooking;
use App\Models\User;

class DirectBookingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view($user, DirectBooking $directBooking): bool
    {
        // Admin can view all
        if ($user instanceof User) {
            return $user->can('view_direct_bookings');
        }

        // Driver can only view if they are assigned
        if ($user instanceof \App\Models\Driver) {
            return $directBooking->activeAssignment && 
                   $directBooking->activeAssignment->driver_id === $user->id;
        }

        return false;
    }

    /**
     * Driver actions
     */
    public function accept($user, DirectBooking $directBooking): bool
    {
        return $this->isAssignedDriver($user, $directBooking) && 
               ($directBooking->status === 'ASSIGNED');
    }

    public function reject($user, DirectBooking $directBooking): bool
    {
        return $this->isAssignedDriver($user, $directBooking) && 
               ($directBooking->status === 'ASSIGNED');
    }

    public function start($user, DirectBooking $directBooking): bool
    {
        return $this->isAssignedDriver($user, $directBooking) && 
               ($directBooking->status === 'ACCEPTED');
    }

    public function end($user, DirectBooking $directBooking): bool
    {
        return $this->isAssignedDriver($user, $directBooking) && 
               ($directBooking->status === 'STARTED');
    }

    protected function isAssignedDriver($user, DirectBooking $directBooking): bool
    {
        return ($user instanceof \App\Models\Driver) && 
               $directBooking->activeAssignment && 
               $directBooking->activeAssignment->driver_id === $user->id;
    }
}
