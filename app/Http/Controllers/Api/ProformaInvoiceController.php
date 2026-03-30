<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProformaInvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $proformaInvoices = ProformaInvoice::query()->with('lineItems')->latest('id')->get();

        return response()->json([
            'message' => 'Proforma invoices fetched successfully.',
            'proformaInvoices' => $proformaInvoices
                ->map(fn(ProformaInvoice $proformaInvoice): array => $this->transformProformaInvoice($proformaInvoice))
                ->values(),
        ]);
    }

    public function show(ProformaInvoice $proformaInvoice): JsonResponse
    {
        $proformaInvoice->load('lineItems');

        return response()->json([
            'message' => 'Proforma invoice fetched successfully.',
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice),
        ]);
    }

    public function convertFromQuotation(Request $request, Quotation $quotation): JsonResponse
    {
        if (ProformaInvoice::query()->where('quotation_id', $quotation->id)->exists()) {
            return response()->json([
                'message' => 'A proforma invoice already exists for this quotation.',
            ], 409);
        }

        $senderId = $request->user()?->id;

        $proformaInvoice = DB::transaction(function () use ($quotation, $senderId): ProformaInvoice {
            $quotation->load('lineItems');

            $proformaInvoice = ProformaInvoice::create([
                'quotation_id' => $quotation->id,
                'lead_id' => $quotation->lead_id,
                'client' => $quotation->client,
                'attention' => $quotation->attention,
                'quote_date' => $quotation->quote_date,
                'notes' => $quotation->notes,
                'day_sections' => $quotation->day_sections,
                'subtotal' => $quotation->subtotal,
                'tax' => $quotation->tax,
                'total' => $quotation->total,
                'status' => 'Sent',
            ]);

            foreach ($quotation->lineItems as $lineItem) {
                $proformaInvoice->lineItems()->create([
                    'day_title' => $lineItem->day_title,
                    'day_description' => $lineItem->day_description,
                    'item' => $lineItem->item,
                    'description' => $lineItem->description,
                    'unit' => $lineItem->unit,
                    'qty' => $lineItem->qty,
                    'rate' => $lineItem->rate,
                    'total' => $lineItem->total,
                ]);
            }

            $quotation->update([
                'status' => 'Converted',
            ]);

            if (!empty($quotation->lead_id)) {
                Lead::query()->whereKey($quotation->lead_id)->update([
                    'booking_status' => 'PI Sent',
                    'pi_sent_by' => $senderId,
                    'pi_sent_at' => now(),
                ]);
            }

            return $proformaInvoice->fresh('lineItems');
        });

        return response()->json([
            'message' => 'Quotation converted to PI successfully.',
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice),
        ], 201);
    }

    public function pdf(ProformaInvoice $proformaInvoice): Response
    {
        $proformaInvoice->load('lineItems');

        $company = [
            'name'                    => Setting::get('company_name', config('app.name')),
            'email'                   => Setting::get('company_email'),
            'phone'                   => Setting::get('company_phone'),
            'address'                 => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
            'currency'                => Setting::get('default_currency', 'TZS'),
            'vat'                     => Setting::get('default_vat', '0'),
        ];

        $pdf = Pdf::loadView('proforma-invoices.pdf', [
            'proformaInvoice' => $this->transformProformaInvoice($proformaInvoice),
            'company'         => $company,
            'logoDataUri'     => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'proforma-invoice-' . $proformaInvoice->id . '.pdf';

        return $pdf->download($filename);
    }

    private function resolveLogoDataUri(): ?string
    {
        $logoPath = Setting::get('logo');

        if (! is_string($logoPath) || $logoPath === '' || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $contents  = Storage::disk('public')->get($logoPath);
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        $mime      = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProformaInvoice(ProformaInvoice $proformaInvoice): array
    {
        return [
            'id' => $proformaInvoice->id,
            'quotationId' => $proformaInvoice->quotation_id,
            'leadId' => $proformaInvoice->lead_id,
            'client' => $proformaInvoice->client,
            'attention' => $proformaInvoice->attention,
            'quoteDate' => optional($proformaInvoice->quote_date)->format('Y-m-d'),
            'notes' => $proformaInvoice->notes,
            'daySections' => $proformaInvoice->day_sections ?? [],
            'lineItems' => $proformaInvoice->lineItems->map(fn($item): array => [
                'id' => $item->id,
                'dayTitle' => $item->day_title,
                'dayDescription' => $item->day_description,
                'item' => $item->item,
                'description' => $item->description,
                'unit' => $item->unit,
                'qty' => (float) $item->qty,
                'rate' => (float) $item->rate,
                'total' => (float) $item->total,
            ])->values(),
            'subtotal' => (float) $proformaInvoice->subtotal,
            'tax' => (float) $proformaInvoice->tax,
            'total' => (float) $proformaInvoice->total,
            'status' => $proformaInvoice->status,
            'createdAt' => $proformaInvoice->created_at?->toISOString(),
            'updatedAt' => $proformaInvoice->updated_at?->toISOString(),
        ];
    }
}
