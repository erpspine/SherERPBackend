<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'vehicle_no',
    'plate_no',
    'make',
    'model',
    'year',
    'seats',
    'chassis',
    'status',
])]
class Vehicle extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'seats' => 'integer',
        ];
    }
}
