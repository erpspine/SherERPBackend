<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationController extends Controller
{
    public function index(): JsonResponse
    {
        $quotations = Quotation::query()
            ->with(['lineItems', 'sentBy', 'lead'])
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Quotations fetched successfully.',
            'quotations' => $quotations->map(fn(Quotation $quotation): array => $this->transformQuotation($quotation))->values(),
        ]);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $quotation->load(['lineItems', 'sentBy', 'lead']);

        return response()->json([
            'message' => 'Quotation fetched successfully.',
            'quotation' => $this->transformQuotation($quotation),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $quotation = DB::transaction(function () use ($validated): Quotation {
            $quotation = Quotation::create([
                'quotation_number' => $this->generateQuotationNumber(),
                'lead_id'      => $validated['leadId'] ?? null,
                'client'       => $validated['client'],
                'attention'    => $validated['attention'],
                'group_name'   => $validated['groupName'] ?? null,
                'quote_date'   => $validated['quoteDate'],
                'notes'        => $validated['notes'] ?? null,
                'day_sections' => $validated['daySections'],
                'subtotal'     => $validated['subtotal'],
                'tax'          => $validated['tax'],
                'total'        => $validated['total'],
                'status'       => 'Pending',
                'sent_by_id'   => null,
                'sent_at'      => null,
            ]);

            foreach ($validated['lineItems'] as $item) {
                $quotation->lineItems()->create($this->mapLineItemRequestToDb($item));
            }

            return $quotation->fresh(['lineItems', 'sentBy', 'lead']);
        });

        return response()->json([
            'message'   => 'Quotation created successfully.',
            'quotation' => $this->transformQuotation($quotation),
        ], 201);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate($this->rules(isUpdate: true, quotationId: $quotation->id));

        // Block setting status to Sent via generic update — use the mark-sent endpoint instead
        if (isset($validated['status']) && $validated['status'] === 'Sent') {
            return response()->json([
                'message' => 'Use the mark-sent endpoint to mark a quotation as Sent.',
            ], 422);
        }

        $quotation = DB::transaction(function () use ($quotation, $validated): Quotation {
            $quotation->update([
                'lead_id'      => array_key_exists('leadId', $validated) ? $validated['leadId'] : $quotation->lead_id,
                'client'       => $validated['client']       ?? $quotation->client,
                'attention'    => $validated['attention']    ?? $quotation->attention,
                'group_name'   => array_key_exists('groupName', $validated) ? $validated['groupName'] : $quotation->group_name,
                'quote_date'   => $validated['quoteDate']    ?? $quotation->quote_date,
                'notes'        => array_key_exists('notes', $validated) ? $validated['notes'] : $quotation->notes,
                'day_sections' => $validated['daySections']  ?? $quotation->day_sections,
                'subtotal'     => $validated['subtotal']     ?? $quotation->subtotal,
                'tax'          => $validated['tax']          ?? $quotation->tax,
                'total'        => $validated['total']        ?? $quotation->total,
                'status'       => $validated['status']       ?? $quotation->status,
            ]);

            if (array_key_exists('lineItems', $validated)) {
                $quotation->lineItems()->delete();
                foreach ($validated['lineItems'] as $item) {
                    $quotation->lineItems()->create($this->mapLineItemRequestToDb($item));
                }
            }

            return $quotation->fresh(['lineItems', 'sentBy', 'lead']);
        });

        return response()->json([
            'message'   => 'Quotation updated successfully.',
            'quotation' => $this->transformQuotation($quotation),
        ]);
    }

    public function markSent(Request $request, Quotation $quotation): JsonResponse
    {
        if ($quotation->status === 'Sent') {
            $quotation->load(['lineItems', 'sentBy', 'lead']);

            return response()->json([
                'message'   => 'Quotation is already marked as Sent.',
                'quotation' => $this->transformQuotation($quotation),
            ]);
        }

        $quotation->update([
            'status'     => 'Sent',
            'sent_by_id' => $request->user()->id,
            'sent_at'    => now(),
        ]);

        // Update linked lead booking status
        if ($quotation->lead_id) {
            Lead::query()->whereKey($quotation->lead_id)->update([
                'booking_status'    => 'Quotation Sent',
                'quotation_sent_by' => $request->user()->id,
                'quotation_sent_at' => now(),
            ]);
        }

        $quotation->load(['lineItems', 'sentBy', 'lead']);

        return response()->json([
            'message'   => 'Quotation marked as Sent.',
            'quotation' => $this->transformQuotation($quotation),
        ]);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();

        return response()->json([
            'message' => 'Quotation deleted successfully.',
        ]);
    }

    public function pdf(Quotation $quotation): Response
    {
        $quotation->load(['lineItems', 'lead']);

        $company = $this->getCompanyPayload();

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $this->transformQuotation($quotation),
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'quotation-' . ($quotation->quotation_number ?? $quotation->id) . '.pdf';

        return $pdf->download($filename);
    }

    public function exportPdf(Request $request): Response
    {
        $rows = $this->buildExportRows($request);
        $company = $this->getCompanyPayload();

        $pdf = Pdf::loadView('quotations.list-pdf', [
            'rows' => $rows,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'statusFilter' => (string) $request->query('status', 'All'),
            'searchTerm' => trim((string) $request->query('search', '')),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('quotations-report.pdf');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $rows = $this->buildExportRows($request);
        $currency = Setting::get('default_currency', 'TZS');

        return response()->streamDownload(function () use ($rows, $currency): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Quote #',
                'Quote Date',
                'Client',
                'Attention',
                'Group Name',
                'Service Summary',
                'Status',
                'Sent By',
                'Sent At',
                'Currency',
                'Subtotal',
                'Tax',
                'Total',
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['quotationNumber'],
                    $row['quoteDate'],
                    $row['client'],
                    $row['attention'],
                    $row['groupName'],
                    $row['serviceSummary'],
                    $row['status'],
                    $row['sentBy'],
                    $row['sentAt'],
                    $currency,
                    $row['subtotal'],
                    $row['tax'],
                    $row['total'],
                ]);
            }

            fclose($output);
        }, 'quotations-report.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $isUpdate = false, ?int $quotationId = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'leadId'                          => [$required, 'nullable', 'integer', 'exists:leads,id'],
            'client'                          => [$required, 'string', 'max:255'],
            'attention'                       => [$required, 'string', 'max:255'],
            'groupName'                       => ['sometimes', 'nullable', 'string', 'max:255'],
            'quoteDate'                       => [$required, 'date'],
            'notes'                           => ['sometimes', 'nullable', 'string'],
            'daySections'                     => [$required, 'array', 'min:1'],
            'daySections.*.dayTitle'          => ['required_with:daySections', 'string', 'max:100'],
            'daySections.*.dayDescription'    => ['sometimes', 'nullable', 'string'],
            'daySections.*.items'             => ['required_with:daySections', 'array'],
            'daySections.*.items.*.item'      => ['required_with:daySections.*.items', 'string', 'max:100'],
            'daySections.*.items.*.description' => ['required_with:daySections.*.items', 'string', 'max:500'],
            'daySections.*.items.*.unit'      => ['required_with:daySections.*.items', 'string', 'max:50'],
            'daySections.*.items.*.qty'       => ['required_with:daySections.*.items', 'numeric', 'min:0'],
            'daySections.*.items.*.rate'      => ['required_with:daySections.*.items', 'numeric', 'min:0'],
            'lineItems'                       => [$required, 'array', 'min:1'],
            'lineItems.*.dayTitle'            => ['required_with:lineItems', 'string', 'max:100'],
            'lineItems.*.dayDescription'      => ['sometimes', 'nullable', 'string'],
            'lineItems.*.item'                => ['required_with:lineItems', 'string', 'max:100'],
            'lineItems.*.description'         => ['required_with:lineItems', 'string', 'max:500'],
            'lineItems.*.unit'                => ['required_with:lineItems', 'string', 'max:50'],
            'lineItems.*.qty'                 => ['required_with:lineItems', 'numeric', 'min:0'],
            'lineItems.*.rate'                => ['required_with:lineItems', 'numeric', 'min:0'],
            'lineItems.*.total'               => ['required_with:lineItems', 'numeric', 'min:0'],
            'subtotal'                        => [$required, 'numeric', 'min:0'],
            'tax'                             => [$required, 'numeric', 'min:0'],
            'total'                           => [$required, 'numeric', 'min:0'],
            'status'                          => ['sometimes', Rule::in(['Pending', 'Sent', 'Approved', 'Rejected', 'Converted'])],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function mapLineItemRequestToDb(array $item): array
    {
        return [
            'day_title'       => $item['dayTitle'],
            'day_description' => $item['dayDescription'] ?? null,
            'item'            => $item['item'],
            'description'     => $item['description'],
            'unit'            => $item['unit'],
            'qty'             => $item['qty'],
            'rate'            => $item['rate'],
            'total'           => $item['total'],
        ];
    }

    private function generateQuotationNumber(): string
    {
        $year  = now()->format('Y');
        $month = now()->format('m');
        $prefix = 'QT-' . $year . '-' . $month . '-';

        $last = Quotation::query()
            ->where('quotation_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('quotation_number')
            ->value('quotation_number');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformQuotation(Quotation $quotation): array
    {
        $lineItems = $quotation->lineItems->map(fn($item): array => [
            'id'             => $item->id,
            'dayTitle'       => $item->day_title,
            'dayDescription' => $item->day_description,
            'item'           => $item->item,
            'description'    => $item->description,
            'unit'           => $item->unit,
            'qty'            => (float) $item->qty,
            'rate'           => (float) $item->rate,
            'total'          => (float) $item->total,
        ])->values()->all();

        $serviceSummary = $this->extractServiceSummary([
            'lineItems' => $lineItems,
            'daySections' => $quotation->day_sections ?? [],
        ], $quotation->lead?->route_parks);

        return [
            'id'              => $quotation->id,
            'quotationNumber' => $quotation->quotation_number,
            'leadId'          => $quotation->lead_id,
            'client'          => $quotation->client,
            'attention'       => $quotation->attention,
            'groupName'       => $quotation->group_name,
            'quoteDate'       => optional($quotation->quote_date)->format('Y-m-d'),
            'notes'           => $quotation->notes,
            'daySections'     => $quotation->day_sections ?? [],
            'lineItems'       => $lineItems,
            'serviceSummary'  => $serviceSummary,
            'subtotal'        => (float) $quotation->subtotal,
            'tax'             => (float) $quotation->tax,
            'total'           => (float) $quotation->total,
            'status'          => $quotation->status,
            'sentById'        => $quotation->sent_by_id,
            'sentBy'          => $quotation->sentBy?->name,
            'sentAt'          => $quotation->sent_at?->toISOString(),
            'createdAt'       => $quotation->created_at?->toISOString(),
            'updatedAt'       => $quotation->updated_at?->toISOString(),
        ];
    }

    private function resolveLogoDataUri(): ?string
    {
        $logoPath = Setting::get('logo');

        if (! is_string($logoPath) || $logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $contents = Storage::disk('public')->get($logoPath);
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCompanyPayload(): array
    {
        return [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
            'currency' => Setting::get('default_currency', 'TZS'),
            'vat' => Setting::get('default_vat', '0'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildExportRows(Request $request): array
    {
        $status = (string) $request->query('status', 'All');
        $search = trim((string) $request->query('search', ''));
        $currency = Setting::get('default_currency', 'TZS');

        return $this->filteredQuotationCollection($status, $search)
            ->map(function (Quotation $quotation) use ($currency): array {
                $transformed = $this->transformQuotation($quotation);

                return [
                    'quotationNumber' => (string) ($transformed['quotationNumber'] ?? ('QT-' . $quotation->id)),
                    'quoteDate' => (string) ($transformed['quoteDate'] ?? '-'),
                    'client' => (string) ($transformed['client'] ?? '-'),
                    'attention' => (string) ($transformed['attention'] ?? '-'),
                    'groupName' => (string) (($transformed['groupName'] ?? '') ?: '-'),
                    'serviceSummary' => $this->extractServiceSummary($transformed),
                    'status' => (string) ($transformed['status'] ?? '-'),
                    'sentBy' => (string) (($transformed['sentBy'] ?? '') ?: '-'),
                    'sentAt' => (string) (($transformed['sentAt'] ?? '') ?: '-'),
                    'subtotal' => (float) ($transformed['subtotal'] ?? 0),
                    'tax' => (float) ($transformed['tax'] ?? 0),
                    'total' => (float) ($transformed['total'] ?? 0),
                    'currency' => $currency,
                ];
            })
            ->values()
            ->all();
    }

    private function filteredQuotationCollection(string $status, string $search): Collection
    {
        $quotations = Quotation::query()
            ->with(['lineItems', 'sentBy', 'lead'])
            ->latest('id')
            ->get();

        if ($status !== 'All') {
            $quotations = $quotations->filter(fn(Quotation $quotation): bool => $quotation->status === $status);
        }

        if ($search === '') {
            return $quotations->values();
        }

        $query = mb_strtolower($search);

        return $quotations->filter(function (Quotation $quotation) use ($query): bool {
            $transformed = $this->transformQuotation($quotation);
            $haystack = [
                (string) ($transformed['quotationNumber'] ?? ''),
                (string) ($transformed['client'] ?? ''),
                (string) ($transformed['groupName'] ?? ''),
                $this->extractServiceSummary($transformed),
            ];

            return str_contains(mb_strtolower(implode(' ', $haystack)), $query);
        })->values();
    }

    /**
     * @param array<string, mixed> $quotation
     */
    private function extractServiceSummary(array $quotation, ?string $leadRoute = null): string
    {
        $normalizedLeadRoute = trim((string) $leadRoute);

        if ($normalizedLeadRoute !== '') {
            return $normalizedLeadRoute;
        }

        $daySections = $quotation['daySections'] ?? [];

        if (is_array($daySections) && $daySections !== []) {
            $routesFromSections = collect($daySections)
                ->map(fn($section) => is_array($section) ? trim((string) ($section['dayDescription'] ?? '')) : '')
                ->filter()
                ->unique()
                ->take(3)
                ->values();

            if ($routesFromSections->isNotEmpty()) {
                return $routesFromSections->implode(' | ');
            }
        }

        $lineItems = $quotation['lineItems'] ?? [];
        if (! is_array($lineItems) || $lineItems === []) {
            return '-';
        }

        $items = collect($lineItems)
            ->map(fn($item) => is_array($item)
                ? trim((string) (($item['dayDescription'] ?? $item['description'] ?? '')))
                : '')
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        return $items->isEmpty() ? '-' : $items->implode(' | ');
    }
}
