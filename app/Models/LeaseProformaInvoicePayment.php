<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lease_proforma_invoice_id',
    'date',
    'amount',
    'method',
    'reference',
    'notes',
])]
class LeaseProformaInvoicePayment extends Model
{
    public function leaseProformaInvoice(): BelongsTo
    {
        return $this->belongsTo(LeaseProformaInvoice::class);
    }

    protected function casts(): array
    {
        return [
            'date'   => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
