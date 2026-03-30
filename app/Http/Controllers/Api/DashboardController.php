<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $currency = (string) Setting::get('default_currency', 'USD');

        $fleetAvailability = $this->buildFleetAvailability();
        $monthlyRevenue = (float) Quotation::query()
            ->whereIn('status', ['Sent', 'Approved', 'Converted'])
            ->whereYear('quote_date', now()->year)
            ->whereMonth('quote_date', now()->month)
            ->sum('total');

        $dashboard = [
            'stats' => [
                'totalFleet' => Vehicle::query()->count(),
                'activeLeases' => $fleetAvailability['activeLeases'],
                'monthlyRevenue' => round($monthlyRevenue, 2),
                'openQuotations' => Quotation::query()->whereIn('status', ['Draft', 'Sent'])->count(),
            ],
            'revenueData' => $this->buildRevenueData(),
            'fleetDistribution' => $this->buildFleetDistribution(),
            'leadActivity' => $this->buildLeadActivity(),
            'recentQuotations' => $this->buildRecentQuotations($currency),
            'fleetAvailability' => $fleetAvailability['items'],
        ];

        return response()->json([
            'message' => 'Dashboard fetched successfully.',
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * @return array{name: string, revenue: float}[]
     */
    private function buildRevenueData(): array
    {
        $months = collect(range(5, 0))->map(fn(int $offset) => now()->copy()->subMonths($offset))->push(now());

        return $months->map(function (Carbon $month): array {
            $revenue = (float) Quotation::query()
                ->whereIn('status', ['Sent', 'Approved', 'Converted'])
                ->whereYear('quote_date', $month->year)
                ->whereMonth('quote_date', $month->month)
                ->sum('total');

            return [
                'name' => $month->format('M'),
                'revenue' => round($revenue, 2),
            ];
        })->values()->all();
    }

    /**
     * @return array{name: string, value: int, color: string}[]
     */
    private function buildFleetDistribution(): array
    {
        $palette = ['#c9a236', '#1d4e5f', '#e31b24', '#0f766e', '#7c3aed', '#d97706'];

        return Vehicle::query()
            ->selectRaw('make, COUNT(*) as total')
            ->groupBy('make')
            ->orderByDesc('total')
            ->get()
            ->values()
            ->map(function ($row, int $index) use ($palette): array {
                return [
                    'name' => (string) $row->make,
                    'value' => (int) $row->total,
                    'color' => $palette[$index % count($palette)],
                ];
            })->all();
    }

    /**
     * @return array{name: string, leads: int}[]
     */
    private function buildLeadActivity(): array
    {
        $start = now()->startOfWeek(Carbon::MONDAY);
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return collect($days)->map(function (string $label, int $index) use ($start): array {
            $date = $start->copy()->addDays($index);

            return [
                'name' => $label,
                'leads' => Lead::query()
                    ->whereDate('created_at', $date->toDateString())
                    ->count(),
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRecentQuotations(string $currency): array
    {
        return Quotation::query()
            ->with('lead')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Quotation $quotation) use ($currency): array {
                $summary = '-';
                if (!empty($quotation->lead?->route_parks)) {
                    $summary = (string) $quotation->lead->route_parks;
                } elseif (is_array($quotation->day_sections) && !empty($quotation->day_sections[0]['dayTitle'])) {
                    $summary = (string) $quotation->day_sections[0]['dayTitle'];
                }

                return [
                    'id' => $quotation->id,
                    'quoteNo' => 'QT-' . (string) now()->year . '-' . str_pad((string) $quotation->id, 4, '0', STR_PAD_LEFT),
                    'client' => $quotation->client,
                    'serviceSummary' => $summary,
                    'amount' => $currency . ' ' . number_format((float) $quotation->total, 2),
                    'status' => $quotation->status,
                ];
            })->values()->all();
    }

    /**
     * @return array{activeLeases: int, items: array<int, array<string, mixed>>}
     */
    private function buildFleetAvailability(): array
    {
        $counts = [
            'Available' => Vehicle::query()->where('status', 'Available')->count(),
            'On Lease' => Vehicle::query()->where('status', 'On Lease')->count(),
            'Maintenance' => Vehicle::query()->where('status', 'Maintenance')->count(),
            'Retired' => Vehicle::query()->where('status', 'Retired')->count(),
        ];

        return [
            'activeLeases' => $counts['On Lease'],
            'items' => [
                [
                    'label' => 'Available',
                    'count' => $counts['Available'],
                    'color' => 'from-green-500 to-emerald-500',
                    'bg' => 'bg-green-500/10',
                    'text' => 'text-green-400',
                ],
                [
                    'label' => 'On Lease',
                    'count' => $counts['On Lease'],
                    'color' => 'from-amber-400 to-amber-600',
                    'bg' => 'bg-blue-500/10',
                    'text' => 'text-blue-400',
                ],
                [
                    'label' => 'Maintenance',
                    'count' => $counts['Maintenance'],
                    'color' => 'from-amber-500 to-yellow-500',
                    'bg' => 'bg-amber-500/10',
                    'text' => 'text-amber-400',
                ],
                [
                    'label' => 'Retired',
                    'count' => $counts['Retired'],
                    'color' => 'from-red-500 to-rose-500',
                    'bg' => 'bg-red-500/10',
                    'text' => 'text-red-400',
                ],
            ],
        ];
    }
}
