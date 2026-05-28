<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'vehicle_id',
    'lease_contract_id',
    'lease_allocation_id',
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

    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class);
    }

    public function leaseAllocation(): BelongsTo
    {
        return $this->belongsTo(LeaseAllocation::class);
    }

    /**
     * Ensure a single lead-level Safari JobCard exists for the given lead, with the supplied
     * itinerary window. Idempotent: if a JobCard already exists it will refresh dates when
     * $overwriteDates is true, otherwise it leaves dates untouched.
     *
     * @return array{jobCardsCreated: int, jobCardId: int}
     */
    public static function ensureForLead(int $leadId, string $startDate, string $endDate, bool $overwriteDates = true): array
    {
        $lead = Lead::query()->find($leadId);

        $jobCard = static::query()->firstOrNew([
            'lead_id' => $leadId,
            'type' => 'Safari',
        ]);

        $created = ! $jobCard->exists;

        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        $fill = [
            'status' => $jobCard->status ?: 'Open',
            'booking_reference_no' => $jobCard->booking_reference_no ?: $lead?->booking_ref,
            'tour_operator_client_name' => $jobCard->tour_operator_client_name ?: $lead?->client_company,
            'contact_person' => $jobCard->contact_person ?: $lead?->agent_contact,
            'contact_number' => $jobCard->contact_number ?: $lead?->agent_phone,
            'contact_email' => $jobCard->contact_email ?: $lead?->agent_email,
            'adults' => $jobCard->adults ?: ($lead?->pax_adults ?? 0),
            'children' => $jobCard->children ?: ($lead?->pax_children ?? 0),
            'nationality' => $jobCard->nationality ?: $lead?->client_country,
            'route_summary' => $jobCard->route_summary ?: $lead?->route_parks,
            'additional_details' => $jobCard->additional_details ?: $lead?->special_requirements,
        ];

        if ($created || $overwriteDates) {
            $fill['safari_start_date'] = $start;
            $fill['safari_end_date'] = $end;
            $fill['number_of_days'] = $start->diffInDays($end) + 1;
        }

        $jobCard->fill($fill);
        $jobCard->save();

        if ($jobCard->job_card_no === null || $jobCard->job_card_no === '') {
            $jobCard->forceFill([
                'job_card_no' => 'JC-' . now()->format('Y') . '-' . str_pad((string) $jobCard->id, 4, '0', STR_PAD_LEFT),
            ])->save();
        }

        return [
            'jobCardsCreated' => $created ? 1 : 0,
            'jobCardId' => (int) $jobCard->id,
        ];
    }

    /**
     * Ensure a single JobCard exists for the given LeaseContract. Idempotent: refreshes
     * key fields (dates, client) from the contract on each call. The contract's first
     * vehicle (if any) is recorded on the job card for convenience.
     *
     * @return array{jobCardsCreated: int, jobCardId: int}
     */
    public static function ensureForLeaseContract(LeaseContract $contract): array
    {
        $jobCard = static::query()->firstOrNew([
            'lease_contract_id' => $contract->id,
            'type' => 'Long Term Lease',
        ]);

        $created = ! $jobCard->exists;

        $contract->loadMissing('vehicles');
        $firstVehicleId = $contract->vehicles->first()?->id;

        $start = $contract->start_date ? \Carbon\Carbon::parse($contract->start_date) : null;
        $end = $contract->end_date ? \Carbon\Carbon::parse($contract->end_date) : null;

        $jobCard->fill([
            'lead_id' => null,
            'vehicle_id' => $firstVehicleId,
            'status' => $jobCard->status ?: 'Open',
            'tour_operator_client_name' => $contract->client_name,
            'safari_start_date' => $start,
            'safari_end_date' => $end,
            'number_of_days' => ($start && $end) ? ($start->diffInDays($end) + 1) : null,
            'additional_details' => $jobCard->additional_details ?: $contract->notes,
        ]);
        $jobCard->save();

        if ($jobCard->job_card_no === null || $jobCard->job_card_no === '') {
            $jobCard->forceFill([
                'job_card_no' => 'JC-' . now()->format('Y') . '-' . str_pad((string) $jobCard->id, 4, '0', STR_PAD_LEFT),
            ])->save();
        }

        return [
            'jobCardsCreated' => $created ? 1 : 0,
            'jobCardId' => (int) $jobCard->id,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lead_id' => 'integer',
            'vehicle_id' => 'integer',
            'lease_contract_id' => 'integer',
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
