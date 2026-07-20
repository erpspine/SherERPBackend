<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'incident_date',
    'vehicle_id',
    'lead_id',
    'lease_allocation_id',
    'report_type',
    'description',
    'action_taken',
    'photos',
    'status',
    'closing_remarks',
])]
class IncidentReport extends Model
{
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function leaseAllocation(): BelongsTo
    {
        return $this->belongsTo(LeaseAllocation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'photos' => 'array',
        ];
    }
}
