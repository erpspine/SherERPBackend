<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
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
])]
class Vehicle extends Model
{
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
        ];
    }
}
