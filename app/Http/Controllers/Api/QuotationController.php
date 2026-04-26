<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class QuotationController extends Controller
{
    public function index(): JsonResponse
    {
        $quotations = Quotation::query()
            ->with(['lineItems', 'sentBy'])
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Quotations fetched successfully.',
            'quotations' => $quotations->map(fn(Quotation $quotation): array => $this->transformQuotation($quotation))->values(),
        ]);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $quotation->load(['lineItems', 'sentBy']);

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
                'lead_id'      => $validated['leadId'] ?? null,
                'client'       => $validated['client'],
                'attention'    => $validated['attention'],
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

            return $quotation->fresh(['lineItems', 'sentBy']);
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

            return $quotation->fresh(['lineItems', 'sentBy']);
        });

        return response()->json([
            'message'   => 'Quotation updated successfully.',
            'quotation' => $this->transformQuotation($quotation),
        ]);
    }

    public function markSent(Request $request, Quotation $quotation): JsonResponse
    {
        if ($quotation->status === 'Sent') {
            $quotation->load(['lineItems', 'sentBy']);

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

        $quotation->load(['lineItems', 'sentBy']);

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
        $quotation->load('lineItems');

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
            'currency' => Setting::get('default_currency', 'TZS'),
            'vat' => Setting::get('default_vat', '0'),
        ];

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $this->transformQuotation($quotation),
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'quotation-' . $quotation->id . '.pdf';

        return $pdf->download($filename);
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

    /**
     * @return array<string, mixed>
     */
    private function transformQuotation(Quotation $quotation): array
    {
        return [
            'id'          => $quotation->id,
            'leadId'      => $quotation->lead_id,
            'client'      => $quotation->client,
            'attention'   => $quotation->attention,
            'quoteDate'   => optional($quotation->quote_date)->format('Y-m-d'),
            'notes'       => $quotation->notes,
            'daySections' => $quotation->day_sections ?? [],
            'lineItems'   => $quotation->lineItems->map(fn($item): array => [
                'id'             => $item->id,
                'dayTitle'       => $item->day_title,
                'dayDescription' => $item->day_description,
                'item'           => $item->item,
                'description'    => $item->description,
                'unit'           => $item->unit,
                'qty'            => (float) $item->qty,
                'rate'           => (float) $item->rate,
                'total'          => (float) $item->total,
            ])->values(),
            'subtotal'    => (float) $quotation->subtotal,
            'tax'         => (float) $quotation->tax,
            'total'       => (float) $quotation->total,
            'status'      => $quotation->status,
            'sentById'    => $quotation->sent_by_id,
            'sentBy'      => $quotation->sentBy?->name,
            'sentAt'      => $quotation->sent_at?->toISOString(),
            'createdAt'   => $quotation->created_at?->toISOString(),
            'updatedAt'   => $quotation->updated_at?->toISOString(),
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
}
