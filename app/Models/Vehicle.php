<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_no',
    'plate_no',
    'make',
    'model',
    'year',
    'seats',
    'mileage',
    'chassis',
    'specs',
    'photo',
    'status',
    'assigned_driver_id',
    'lease_type',
    'lease_start_date',
    'lease_end_date',
    'lease_client_name',
    'lease_monthly_rate',
    'lease_notes',
])]
class Vehicle extends Model
{
    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function vehicleServices(): HasMany
    {
        return $this->hasMany(VehicleService::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'seats' => 'integer',
            'mileage' => 'integer',
            'assigned_driver_id' => 'integer',
            'lease_start_date' => 'date',
            'lease_end_date' => 'date',
            'lease_monthly_rate' => 'decimal:2',
        ];
    }
}
