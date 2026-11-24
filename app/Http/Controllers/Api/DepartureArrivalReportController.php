<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\SafariAllocation;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DepartureArrivalReportController extends Controller
{
    public function pdf(Request $request): Response
    {
        $this->authorize('viewAny', JobCard::class);
        [$rows, $filters] = $this->reportData($request);

        $pdf = Pdf::loadView('departure-arrival-report.pdf', [
            'rows' => $rows,
            'company' => $this->company(),
            'logoDataUri' => $this->resolveLogoDataUri(),
            'filters' => $filters,
            'generatedAt' => now()->format('d M Y H:i'),
            'stats' => $this->stats($rows),
        ])->setPaper('a4', 'landscape');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(744, 570, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.42, 0.45, 0.50]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="departure-arrival-report-' . now()->format('Ymd-His') . '.pdf"',
        ]);
    }

    public function excel(Request $request): Response
    {
        $this->authorize('viewAny', JobCard::class);
        [$rows, $filters] = $this->reportData($request);
        $content = view('departure-arrival-report.excel', [
            'rows' => $rows,
            'company' => $this->company(),
            'filters' => $filters,
            'generatedAt' => now()->format('d M Y H:i'),
            'stats' => $this->stats($rows),
        ])->render();

        return response("\xEF\xBB\xBF" . $content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="departure-arrival-report-' . now()->format('Ymd-His') . '.xls"',
        ]);
    }

    /** @return array{0: Collection<int, array<string, mixed>>, 1: array<string, string>} */
    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
        ]);
        $dateFrom = $validated['dateFrom'] ?? '';
        $dateTo = $validated['dateTo'] ?? '';

        $cardQuery = JobCard::query()->with([
            'lead.quotations',
            'vehicle',
            'leaseContract',
            'leaseAllocation.vehicle',
            'leaseAllocation.driver',
            'leaseAllocation.leaseContract',
            'safariAllocations.vehicle',
            'safariAllocations.driver',
        ]);
        if ($request->user()?->hasRole('Driver')) {
            $cardQuery->whereIn('lead_id', SafariAllocation::query()
                ->where('driver_id', $request->user()->id)
                ->select('lead_id'));
        }

        $cards = $cardQuery->get();
        $rows = collect();
        $cardLeadIds = $cards->pluck('lead_id')->filter()->map(fn($id) => (string) $id)->unique();

        foreach ($cards as $card) {
            [$start, $end] = $this->jobCardDates($card);
            if ($start === null && $end === null) {
                continue;
            }
            $details = $this->jobCardDetails($card);
            foreach ($details as $detail) {
                if ($start !== null && $this->inRange($start, $dateFrom, $dateTo)) {
                    $rows->push($this->movementRow($start, 'Departure', $card, $detail));
                }
                if ($end !== null && $this->inRange($end, $dateFrom, $dateTo)) {
                    $rows->push($this->movementRow($end, 'Arrival', $card, $detail));
                }
            }
        }

        $allocationQuery = SafariAllocation::query()->with(['lead', 'vehicle', 'driver']);
        if ($request->user()?->hasRole('Driver')) {
            $allocationQuery->where('driver_id', $request->user()->id);
        }
        foreach ($allocationQuery->get()->filter(fn($allocation) => ! $cardLeadIds->contains((string) $allocation->lead_id)) as $allocation) {
            $detail = $this->allocationDetail($allocation);
            $start = optional($allocation->start_date)->toDateString() ?: optional($allocation->lead?->start_date)->toDateString();
            $end = optional($allocation->end_date)->toDateString() ?: optional($allocation->lead?->end_date)->toDateString();
            $virtualCard = new JobCard([
                'job_card_no' => 'Allocation #' . $allocation->id,
                'type' => 'Safari',
                'status' => $allocation->status,
            ]);
            if ($start && $this->inRange($start, $dateFrom, $dateTo)) {
                $rows->push($this->movementRow($start, 'Departure', $virtualCard, $detail));
            }
            if ($end && $this->inRange($end, $dateFrom, $dateTo)) {
                $rows->push($this->movementRow($end, 'Arrival', $virtualCard, $detail));
            }
        }

        $rows = $rows->sortBy(fn($row) => $row['date'] . ($row['movement'] === 'Departure' ? '0' : '1') . $row['vehicle'])->values();

        return [$rows, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]];
    }

    /** @return array{0: ?string, 1: ?string} */
    private function jobCardDates(JobCard $card): array
    {
        $itinerary = is_array($card->route_itinerary) ? $card->route_itinerary : [];
        if (empty($itinerary) && $card->lead) {
            $quotation = $card->lead->quotations->sortByDesc(fn($quote) => $quote->quote_date?->timestamp ?: $quote->id)->first();
            $itinerary = is_array($quotation?->day_sections) ? $quotation->day_sections : [];
        }

        $dates = collect($itinerary)
            ->map(function ($item): ?string {
                $value = is_array($item)
                    ? ($item['date'] ?? $item['dayDate'] ?? $item['dayTitle'] ?? null)
                    : $item;
                return $this->normalizeDate($value);
            })
            ->filter()
            ->sort()
            ->values();

        return [
            $dates->first() ?: optional($card->safari_start_date)->toDateString(),
            $dates->last() ?: optional($card->safari_end_date)->toDateString(),
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, substr($value, 0, 10));
                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                // Try the next supported itinerary format.
            }
        }
        return null;
    }

    /** @return Collection<int, array<string, string>> */
    private function jobCardDetails(JobCard $card): Collection
    {
        $isSafari = str_starts_with(strtolower((string) $card->type), 'safari');
        if ($isSafari) {
            $allocations = $card->safariAllocations;
            if ($card->vehicle_id) {
                $allocations = $allocations->where('vehicle_id', $card->vehicle_id);
            }
            if ($allocations->isNotEmpty()) {
                return $allocations->map(fn($allocation) => $this->allocationDetail($allocation))->values();
            }
        }

        $vehicle = $card->vehicle ?: $card->leaseAllocation?->vehicle;
        $driver = $card->leaseAllocation?->driver;
        $contract = $card->leaseContract ?: $card->leaseAllocation?->leaseContract;

        return collect([[
            'vehicle' => $this->vehicleLabel($vehicle),
            'driver' => $driver?->name ?: $card->driver_details ?: 'Driver details unavailable',
            'contact' => $driver?->phone ?: $card->contact_number ?: '',
            'group' => $card->lead?->group_name ?: $card->leaseAllocation?->group_name ?: $contract?->group_name ?: 'Group unavailable',
            'client' => $card->tour_operator_client_name ?: $card->lead?->client_company ?: $contract?->client_name ?: 'Client unavailable',
        ]]);
    }

    /** @return array<string, string> */
    private function allocationDetail(SafariAllocation $allocation): array
    {
        return [
            'vehicle' => $this->vehicleLabel($allocation->vehicle),
            'driver' => $allocation->driver?->name ?: 'Driver details unavailable',
            'contact' => $allocation->driver?->phone ?: '',
            'group' => $allocation->lead?->group_name ?: 'Group unavailable',
            'client' => $allocation->lead?->client_company ?: 'Client unavailable',
        ];
    }

    private function vehicleLabel(mixed $vehicle): string
    {
        if (! $vehicle) {
            return 'Vehicle details unavailable';
        }
        $number = trim((string) ($vehicle->vehicle_no ?? ''));
        $plate = trim((string) ($vehicle->plate_no ?? ''));
        return $plate !== '' ? trim($number . ' (' . $plate . ')') : ($number ?: 'Vehicle details unavailable');
    }

    /** @param array<string, string> $detail @return array<string, string> */
    private function movementRow(string $date, string $movement, JobCard $card, array $detail): array
    {
        return [
            'date' => $date,
            'movement' => $movement,
            'jobCard' => $card->job_card_no ?: '-',
            'type' => $card->type ?: '-',
            'status' => $card->status ?: '-',
            ...$detail,
        ];
    }

    private function inRange(string $date, string $from, string $to): bool
    {
        return ($from === '' || $date >= $from) && ($to === '' || $date <= $to);
    }

    /** @return array<string, string|null> */
    private function company(): array
    {
        return [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
        ];
    }

    /** @return array<string, int> */
    private function stats(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'departures' => $rows->where('movement', 'Departure')->count(),
            'arrivals' => $rows->where('movement', 'Arrival')->count(),
        ];
    }

    private function resolveLogoDataUri(): ?string
    {
        $path = Setting::get('logo');
        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }
}
