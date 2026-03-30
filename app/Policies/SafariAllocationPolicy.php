<?php

namespace App\Policies;

use App\Models\SafariAllocation;
use App\Models\User;

class SafariAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('safari-allocations.view');
    }

    public function view(User $user, SafariAllocation $safariAllocation): bool
    {
        if (! $user->can('safari-allocations.view')) {
            return false;
        }

        if (! $user->hasRole('Driver')) {
            return true;
        }

        return (int) $safariAllocation->driver_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('safari-allocations.create') && $user->can('vehicles.assign');
    }

    public function update(User $user, SafariAllocation $safariAllocation): bool
    {
        return $user->can('safari-allocations.update') && $user->can('vehicles.assign');
    }

    public function delete(User $user, SafariAllocation $safariAllocation): bool
    {
        return $user->can('safari-allocations.delete');
    }
}
