<?php

namespace App\Policies;

use App\Models\JobCard;
use App\Models\SafariAllocation;
use App\Models\User;

class JobCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('job-cards.view');
    }

    public function view(User $user, JobCard $jobCard): bool
    {
        if (! $user->can('job-cards.view')) {
            return false;
        }

        if (! $user->hasRole('Driver')) {
            return true;
        }

        return $this->driverCanAccessJobCard($user, $jobCard);
    }

    public function create(User $user): bool
    {
        return $user->can('job-cards.create');
    }

    public function update(User $user, JobCard $jobCard): bool
    {
        return $user->can('job-cards.update');
    }

    public function delete(User $user, JobCard $jobCard): bool
    {
        return $user->can('job-cards.delete');
    }

    private function driverCanAccessJobCard(User $user, JobCard $jobCard): bool
    {
        if ($jobCard->lead_id === null) {
            return false;
        }

        return SafariAllocation::query()
            ->where('driver_id', $user->id)
            ->where('lead_id', $jobCard->lead_id)
            ->exists();
    }
}
