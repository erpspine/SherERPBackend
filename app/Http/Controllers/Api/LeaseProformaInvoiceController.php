<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaseContract;
use App\Models\LeaseProformaInvoice;
use App\Models\LeaseProformaInvoicePayment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class LeaseProformaInvoiceController extends Controller
{
    private const STATUSES = ['Draft', 'Sent', 'Confirmed', 'Deposit', 'Paid', 'Cancelled'];

    // ─────────────────────────────────────────────
    // List & show
    // ─────────────────────────────────────────────

    public function index(): JsonResponse
    {
        $invoices = LeaseProformaInvoice::query()
            ->with(['leaseContract', 'payments'])
            ->latest('id')
            ->get();

        return response()->json([
            'message'  => 'Lease proforma invoices fetched successfully.',
            'invoices' => $invoices->map(fn($i): array => $this->transform($i))->values(),
        ]);
    }

    public function show(LeaseProformaInvoice $leaseProformaInvoice): JsonResponse
    {
        $leaseProformaInvoice->load(['leaseContract', 'payments']);

        return response()->json([
            'message' => 'Lease proforma invoice fetched successfully.',
            'invoice' => $this->transform($leaseProformaInvoice),
        ]);
    }

    public function pdf(LeaseProformaInvoice $leaseProformaInvoice): Response
    {
        $leaseProformaInvoice->load(['leaseContract', 'payments']);

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
        ];

        $pdf = Pdf::loadView('lease-proforma-invoices.pdf', [
            'leaseProformaInvoice' => $this->transform($leaseProformaInvoice),
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $filename = 'lease-proforma-invoice-' . ($leaseProformaInvoice->proforma_number ?: $leaseProformaInvoice->id) . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─────────────────────────────────────────────
    // Create / update / delete
    // ─────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $invoice = LeaseProformaInvoice::create([
            'proforma_number'   => $this->generateNumber(),
            'lease_contract_id' => $validated['leaseContractId'],
            'client_name'       => $validated['clientName'],
            'currency'          => $validated['currency'] ?? 'USD',
            'attention'         => $validated['attention'] ?? null,
            'invoice_date'      => $validated['invoiceDate'],
            'notes'             => $validated['notes'] ?? null,
            'line_items'        => $validated['lineItems'] ?? [],
            'subtotal'          => $validated['subtotal'],
            'tax'               => $validated['tax'],
            'total'             => $validated['total'],
            'status'            => $validated['status'] ?? 'Sent',
        ]);

        $invoice->load(['leaseContract', 'payments']);

        return response()->json([
            'message' => 'Lease proforma invoice created successfully.',
            'invoice' => $this->transform($invoice),
        ], 201);
    }

    public function update(Request $request, LeaseProformaInvoice $leaseProformaInvoice): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $leaseProformaInvoice->update([
            'lease_contract_id' => $validated['leaseContractId'],
            'client_name'       => $validated['clientName'],
            'currency'          => $validated['currency'] ?? $leaseProformaInvoice->currency ?? 'USD',
            'attention'         => $validated['attention'] ?? null,
            'invoice_date'      => $validated['invoiceDate'],
            'notes'             => $validated['notes'] ?? null,
            'line_items'        => $validated['lineItems'] ?? [],
            'subtotal'          => $validated['subtotal'],
            'tax'               => $validated['tax'],
            'total'             => $validated['total'],
            'status'            => $validated['status'] ?? $leaseProformaInvoice->status,
        ]);

        $leaseProformaInvoice->load(['leaseContract', 'payments']);

        return response()->json([
            'message' => 'Lease proforma invoice updated successfully.',
            'invoice' => $this->transform($leaseProformaInvoice),
        ]);
    }

    public function destroy(LeaseProformaInvoice $leaseProformaInvoice): JsonResponse
    {
        $leaseProformaInvoice->delete();

        return response()->json(['message' => 'Lease proforma invoice deleted successfully.']);
    }

    // ─────────────────────────────────────────────
    // Payments
    // ─────────────────────────────────────────────

    public function addPayment(Request $request, LeaseProformaInvoice $leaseProformaInvoice): JsonResponse
    {
        $validated = $request->validate([
            'date'      => ['required', 'date'],
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'method'    => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ]);

        $leaseProformaInvoice->payments()->create([
            'date'      => $validated['date'],
            'amount'    => $validated['amount'],
            'method'    => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes'     => $validated['notes'] ?? null,
        ]);

        $leaseProformaInvoice->load('payments');
        $this->recalculateStatus($leaseProformaInvoice);
        $leaseProformaInvoice->load(['leaseContract', 'payments']);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'invoice' => $this->transform($leaseProformaInvoice),
        ], 201);
    }

    public function deletePayment(
        LeaseProformaInvoice $leaseProformaInvoice,
        LeaseProformaInvoicePayment $payment
    ): JsonResponse {
        if ((int) $payment->lease_proforma_invoice_id !== (int) $leaseProformaInvoice->id) {
            return response()->json(['message' => 'Payment does not belong to this invoice.'], 404);
        }

        $payment->delete();

        $leaseProformaInvoice->load('payments');
        $this->recalculateStatus($leaseProformaInvoice, afterDelete: true);
        $leaseProformaInvoice->load(['leaseContract', 'payments']);

        return response()->json([
            'message' => 'Payment deleted successfully.',
            'invoice' => $this->transform($leaseProformaInvoice),
        ]);
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    private function recalculateStatus(LeaseProformaInvoice $invoice, bool $afterDelete = false): void
    {
        $paid  = (float) $invoice->payments->sum('amount');
        $total = (float) $invoice->total;

        if ($paid >= $total && $total > 0) {
            $invoice->status = 'Paid';
        } elseif ($paid > 0) {
            $invoice->status = 'Deposit';
        } elseif ($afterDelete && in_array($invoice->status, ['Deposit', 'Paid'], true)) {
            $invoice->status = 'Confirmed';
        }

        $invoice->save();
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'leaseContractId' => ['required', 'integer', 'exists:lease_contracts,id'],
            'clientName'      => ['required', 'string', 'max:150'],
            'currency'        => ['nullable', Rule::in(['USD', 'TSh'])],
            'attention'       => ['nullable', 'string', 'max:150'],
            'invoiceDate'     => ['required', 'date_format:Y-m-d'],
            'notes'           => ['nullable', 'string', 'max:2000'],
            'lineItems'       => ['required', 'array', 'min:1'],
            'lineItems.*.description' => ['required', 'string', 'max:300'],
            'lineItems.*.noVehicles'  => ['required', 'numeric', 'min:1'],
            'lineItems.*.noDays'      => ['required', 'numeric', 'min:1'],
            'lineItems.*.rate'        => ['required', 'numeric', 'min:0'],
            'lineItems.*.total'       => ['required', 'numeric', 'min:0'],
            'subtotal'        => ['required', 'numeric', 'min:0'],
            'tax'             => ['required', 'numeric', 'min:0'],
            'total'           => ['required', 'numeric', 'min:0'],
            'status'          => ['nullable', Rule::in(self::STATUSES)],
        ]);
    }

    private function generateNumber(): string
    {
        $prefix = 'LPI-' . now()->format('Y') . '-' . now()->format('m') . '-';

        $last = LeaseProformaInvoice::query()
            ->where('proforma_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('proforma_number')
            ->value('proforma_number');

        $seq = $last ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function resolveLogoDataUri(): ?string
    {
        $logoPath = Setting::get('logo');

        if (!is_string($logoPath) || $logoPath === '' || !Storage::disk('public')->exists($logoPath)) {
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

    private function transform(LeaseProformaInvoice $invoice): array
    {
        $payments   = $invoice->relationLoaded('payments') ? $invoice->payments : $invoice->payments()->get();
        $paidAmount = (float) $payments->sum('amount');
        $total      = (float) $invoice->total;

        return [
            'id'               => $invoice->id,
            'proformaNumber'   => $invoice->proforma_number,
            'leaseContractId'  => $invoice->lease_contract_id,
            'contract'         => $invoice->leaseContract ? [
                'id'         => $invoice->leaseContract->id,
                'clientName' => $invoice->leaseContract->client_name,
                'groupName'  => $invoice->leaseContract->group_name,
                'leaseType'  => $invoice->leaseContract->lease_type,
                'startDate'  => optional($invoice->leaseContract->start_date)->format('Y-m-d'),
                'endDate'    => optional($invoice->leaseContract->end_date)->format('Y-m-d'),
            ] : null,
            'clientName'       => $invoice->client_name,
            'currency'         => strtoupper($invoice->currency ?: 'USD'),
            'attention'        => $invoice->attention,
            'invoiceDate'      => optional($invoice->invoice_date)->format('Y-m-d'),
            'notes'            => $invoice->notes,
            'lineItems'        => array_map(fn(array $item): array => [
                'description' => $item['description'] ?? '',
                'noVehicles'  => (float) ($item['noVehicles'] ?? 0),
                'noDays'      => (float) ($item['noDays'] ?? 0),
                'rate'        => (float) ($item['rate'] ?? 0),
                'total'       => (float) ($item['total'] ?? 0),
            ], is_array($invoice->line_items) ? $invoice->line_items : []),
            'subtotal'         => (float) $invoice->subtotal,
            'tax'              => (float) $invoice->tax,
            'total'            => $total,
            'status'           => $invoice->status,
            'paidAmount'       => $paidAmount,
            'balance'          => max(0.0, $total - $paidAmount),
            'payments'         => $payments->sortBy('date')->values()->map(fn($p): array => [
                'id'                     => $p->id,
                'leaseProformaInvoiceId' => $p->lease_proforma_invoice_id,
                'date'                   => optional($p->date)->format('Y-m-d'),
                'amount'                 => (float) $p->amount,
                'method'                 => $p->method,
                'reference'              => $p->reference,
                'notes'                  => $p->notes,
            ])->all(),
            'createdAt'        => $invoice->created_at?->toISOString(),
            'updatedAt'        => $invoice->updated_at?->toISOString(),
        ];
    }
}
