<?php

namespace App\Policies;

use App\Models\OdometerLog;
use App\Models\User;

class OdometerLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('odometer-logs.view');
    }

    public function view(User $user, OdometerLog $odometerLog): bool
    {
        if (! $user->can('odometer-logs.view')) {
            return false;
        }

        if (! $user->hasRole('Driver')) {
            return true;
        }

        // Drivers can only see logs that belong to a trip they own.
        return (int) $odometerLog->safariAllocation?->driver_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('odometer-logs.create');
    }

    public function update(User $user, OdometerLog $odometerLog): bool
    {
        if (! $user->can('odometer-logs.update')) {
            return false;
        }

        if (! $user->hasRole('Driver')) {
            return true;
        }

        // Drivers can only edit their own readings for their own trips.
        return (int) $odometerLog->safariAllocation?->driver_id === (int) $user->id
            && (int) $odometerLog->user_id === (int) $user->id;
    }

    public function delete(User $user, OdometerLog $odometerLog): bool
    {
        return $user->can('odometer-logs.delete');
    }
}
