<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_contract_id',
        'group_name',
        'vehicle_id',
        'driver_id',
        'start_date',
        'end_date',
        'itinerary',
        'itinerary_items',
        'fuel_notes',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'itinerary_items' => 'array',
    ];

    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
