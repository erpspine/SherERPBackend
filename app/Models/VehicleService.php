<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_id',
    'service_center',
    'service_type',
    'service_date_out',
    'service_date_in',
    'odometer_out',
    'odometer_in',
    'fuel_out',
    'fuel_in',
    'cost',
    'notes',
    'status',
])]
class VehicleService extends Model
{
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_date_out' => 'date',
            'service_date_in' => 'date',
            'odometer_out' => 'integer',
            'odometer_in' => 'integer',
            'fuel_out' => 'integer',
            'fuel_in' => 'integer',
            'cost' => 'decimal:2',
        ];
    }
}
