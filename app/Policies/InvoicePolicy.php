<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function approve(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('Finance') && $user->can('invoices.approve');
    }
}
