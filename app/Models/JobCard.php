<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'job_card_no',
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
    'number_of_days',
    'route_summary',
    'route_itinerary',
    'pickup_location',
    'dropoff_location',
    'additional_details',
])]
class JobCard extends Model
{
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lead_id' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'number_of_days' => 'integer',
            'route_itinerary' => 'array',
            'safari_start_date' => 'date',
            'safari_end_date' => 'date',
        ];
    }
}
