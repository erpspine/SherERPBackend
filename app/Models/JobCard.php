<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'vehicle_id',
    'job_card_no',
    'type',
    'status',
    'booking_reference_no',
    'tour_operator_client_name',
    'contact_person',
    'contact_number',
    'contact_email',
    'adults',
    'children',
    'nationality',
    'guide_language',
    'safari_start_date',
    'safari_end_date',
    'time_out',
    'time_in',
    'number_of_days',
    'route_summary',
    'route_itinerary',
    'pickup_location',
    'dropoff_location',
    'additional_details',
    'reason',
    'client_details',
    'location',
    'kms',
    'odometer_out',
    'odometer_in',
    'mileage',
    'fuel_gauge_out',
    'fuel_gauge_in',
    'approximate_fuel_used',
    'driver_details',
])]
class JobCard extends Model
{
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

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
            'lead_id' => 'integer',
            'vehicle_id' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'number_of_days' => 'integer',
            'kms' => 'decimal:2',
            'odometer_out' => 'integer',
            'odometer_in' => 'integer',
            'mileage' => 'integer',
            'fuel_gauge_out' => 'decimal:2',
            'fuel_gauge_in' => 'decimal:2',
            'approximate_fuel_used' => 'decimal:2',
            'route_itinerary' => 'array',
            'safari_start_date' => 'date',
            'safari_end_date' => 'date',
            'time_out' => 'datetime:H:i:s',
            'time_in' => 'datetime:H:i:s',
        ];
    }
}
