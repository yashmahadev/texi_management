<?php

namespace App\Policies;

use App\Models\DailyDutyLog;
use App\Models\User;

class DailyDutyLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_daily_logs');
    }

    public function view(User $user, DailyDutyLog $log): bool
    {
        return $user->hasPermissionTo('view_daily_logs');
    }

    public function update(User $user, DailyDutyLog $log): bool
    {
        return $user->hasPermissionTo('verify_daily_logs');
    }
    
    // Note: Driver actions (start/end) are handled by specific controller logic checking driver ownership,
    // but could be added here if we passed Driver model instead of User.
}
