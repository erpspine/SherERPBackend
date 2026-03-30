<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\SafariAllocation;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\JobCardPolicy;
use App\Policies\SafariAllocationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Invoice::class => InvoicePolicy::class,
        JobCard::class => JobCardPolicy::class,
        SafariAllocation::class => SafariAllocationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole('Admin') ? true : null;
        });
    }
}
