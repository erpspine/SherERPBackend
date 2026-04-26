<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'user_id',
    'litres',
    'base_rate_per_km',
    'reason',
    'transport_itinerary',
    'total_distance_km',
    'total_fuel_litres',
    'status',
    'responded_by',
    'approved_by',
    'rejected_by',
    'amended_by',
    'responded_at',
    'response_note',
])]
class FuelRequisition extends Model
{
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'amended_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'litres' => 'decimal:2',
            'base_rate_per_km' => 'decimal:4',
            'transport_itinerary' => 'array',
            'total_distance_km' => 'decimal:2',
            'total_fuel_litres' => 'decimal:2',
            'responded_at' => 'datetime',
        ];
    }
}
