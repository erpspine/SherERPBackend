<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'proforma_number',
    'lease_contract_id',
    'client_name',
    'currency',
    'attention',
    'invoice_date',
    'notes',
    'line_items',
    'subtotal',
    'tax',
    'total',
    'status',
])]
class LeaseProformaInvoice extends Model
{
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LeaseProformaInvoicePayment::class);
    }

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'line_items'   => 'array',
            'subtotal'     => 'decimal:2',
            'tax'          => 'decimal:2',
            'total'        => 'decimal:2',
        ];
    }
}
