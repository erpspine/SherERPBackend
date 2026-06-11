<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        $invoices = Invoice::query()
            ->with(['proformaInvoice', 'payments'])
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Invoices fetched successfully.',
            'invoices' => $invoices->map(fn(Invoice $invoice): array => $this->transform($invoice))->values(),
        ]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Invoice fetched successfully.',
            'invoice' => $this->transform($invoice),
        ]);
    }

    public function payments(Invoice $invoice): JsonResponse
    {
        $invoice->load('payments');

        return response()->json([
            'message' => 'Invoice payments fetched successfully.',
            'invoiceId' => $invoice->id,
            'payments' => $invoice->payments
                ->sortBy('date')
                ->values()
                ->map(fn($payment): array => $this->transformPayment($payment))
                ->all(),
        ]);
    }

    public function allPayments(): JsonResponse
    {
        $payments = \App\Models\InvoicePayment::query()
            ->with('invoice')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'All invoice payments fetched successfully.',
            'payments' => $payments->map(fn($payment): array => [
                'id' => $payment->id,
                'invoiceId' => $payment->invoice_id,
                'date' => optional($payment->date)->format('Y-m-d'),
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'notes' => $payment->notes,
                'createdAt' => $payment->created_at?->toISOString(),
                'updatedAt' => $payment->updated_at?->toISOString(),
                'invoice' => $payment->invoice ? [
                    'id' => $payment->invoice->id,
                    'invoiceNo' => $payment->invoice->invoice_no,
                    'client' => $payment->invoice->client,
                ] : null,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $invoice = Invoice::create([
            'proforma_invoice_id' => $validated['proformaInvoiceId'] ?? null,
            'invoice_no' => $validated['invoiceNo'],
            'quickbooks_ref' => $validated['quickbooksRef'] ?? null,
            'client' => $validated['client'],
            'issue_date' => $validated['issueDate'],
            'due_date' => $validated['dueDate'] ?? null,
            'total' => $validated['total'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Invoice created successfully.',
            'invoice' => $this->transform($invoice),
        ], 201);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if ($request->has('dueDate') && ! $request->has('issueDate')) {
            $request->merge([
                'issueDate' => optional($invoice->issue_date)->format('Y-m-d'),
            ]);
        }

        $validated = $request->validate($this->rules(isUpdate: true, invoiceId: $invoice->id));

        $invoice->update([
            'proforma_invoice_id' => array_key_exists('proformaInvoiceId', $validated)
                ? $validated['proformaInvoiceId']
                : $invoice->proforma_invoice_id,
            'invoice_no' => $validated['invoiceNo'] ?? $invoice->invoice_no,
            'quickbooks_ref' => array_key_exists('quickbooksRef', $validated)
                ? $validated['quickbooksRef']
                : $invoice->quickbooks_ref,
            'client' => $validated['client'] ?? $invoice->client,
            'issue_date' => $validated['issueDate'] ?? $invoice->issue_date,
            'due_date' => array_key_exists('dueDate', $validated) ? $validated['dueDate'] : $invoice->due_date,
            'total' => $validated['total'] ?? $invoice->total,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $invoice->notes,
        ]);

        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Invoice updated successfully.',
            'invoice' => $this->transform($invoice),
        ]);
    }

    public function approve(Invoice $invoice): JsonResponse
    {
        $this->authorize('approve', $invoice);

        if ($invoice->status === 'approved') {
            return response()->json([
                'message' => 'Invoice is already approved.',
            ], 422);
        }

        $invoice->update(['status' => 'approved']);
        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Invoice approved successfully.',
            'invoice' => $this->transform($invoice),
        ]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully.',
        ]);
    }

    public function addPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $invoice->payments()->create([
            'date' => $validated['date'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'payment' => $this->transformPayment($payment),
            'invoice' => $this->transform($invoice),
        ], 201);
    }

    public function deletePayment(Invoice $invoice, InvoicePayment $payment): JsonResponse
    {
        if ((int) $payment->invoice_id !== (int) $invoice->id) {
            return response()->json([
                'message' => 'Payment does not belong to the selected invoice.',
            ], 404);
        }

        $payment->delete();
        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Payment deleted successfully.',
            'invoice' => $this->transform($invoice),
        ]);
    }

    public function uploadAttachment(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'attachment' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv'],
        ]);

        if (! empty($invoice->attachment_path)) {
            Storage::disk('public')->delete($invoice->attachment_path);
        }

        $file = $validated['attachment'];
        $path = $file->store('invoice-attachments', 'public');

        $invoice->update([
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
        ]);

        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Invoice attachment uploaded successfully.',
            'invoice' => $this->transform($invoice),
        ]);
    }

    public function removeAttachment(Invoice $invoice): JsonResponse
    {
        if (! empty($invoice->attachment_path)) {
            Storage::disk('public')->delete($invoice->attachment_path);
        }

        $invoice->update([
            'attachment_path' => null,
            'attachment_name' => null,
        ]);

        $invoice->load(['proformaInvoice', 'payments']);

        return response()->json([
            'message' => 'Invoice attachment removed successfully.',
            'invoice' => $this->transform($invoice),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $isUpdate = false, ?int $invoiceId = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'proformaInvoiceId' => ['nullable', 'integer', 'exists:proforma_invoices,id'],
            'invoiceNo' => [
                $required,
                'string',
                'max:100',
                Rule::unique('invoices', 'invoice_no')->ignore($invoiceId),
            ],
            'quickbooksRef' => ['sometimes', 'nullable', 'string', 'max:100'],
            'client' => [$required, 'string', 'max:255'],
            'issueDate' => [$required, 'date'],
            'dueDate' => ['sometimes', 'nullable', 'date', 'after_or_equal:issueDate'],
            'total' => [$required, 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Invoice $invoice): array
    {
        $payments = $invoice->relationLoaded('payments')
            ? $invoice->payments
            : $invoice->payments()->get();

        $paidAmount = (float) $payments->sum('amount');
        $total = (float) $invoice->total;

        return [
            'id' => $invoice->id,
            'proformaInvoiceId' => $invoice->proforma_invoice_id,
            'invoiceNo' => $invoice->invoice_no,
            'quickbooksRef' => $invoice->quickbooks_ref,
            'client' => $invoice->client,
            'issueDate' => optional($invoice->issue_date)->format('Y-m-d'),
            'dueDate' => optional($invoice->due_date)->format('Y-m-d'),
            'total' => $total,
            'paidAmount' => $paidAmount,
            'balance' => max(0, $total - $paidAmount),
            'status' => $invoice->status ?? 'pending',
            'notes' => $invoice->notes,
            'attachmentName' => $invoice->attachment_name,
            'attachmentUrl' => $invoice->attachment_path ? Storage::disk('public')->url($invoice->attachment_path) : null,
            'createdAt' => $invoice->created_at?->toISOString(),
            'updatedAt' => $invoice->updated_at?->toISOString(),
            'proformaInvoice' => $invoice->proformaInvoice ? [
                'id' => $invoice->proformaInvoice->id,
                'piNo' => $invoice->proformaInvoice->proforma_number
                    ?: 'PI-' . optional($invoice->proformaInvoice->created_at)->format('Y-m') . '-' . str_pad((string) $invoice->proformaInvoice->id, 3, '0', STR_PAD_LEFT),
            ] : null,
            'payments' => $payments
                ->sortBy('date')
                ->values()
                ->map(fn($payment): array => $this->transformPayment($payment))
                ->all(),
        ];
    }

    /**
     * @param  mixed  $payment
     * @return array<string, mixed>
     */
    private function transformPayment($payment): array
    {
        return [
            'id' => $payment->id,
            'invoiceId' => $payment->invoice_id,
            'date' => optional($payment->date)->format('Y-m-d'),
            'amount' => (float) $payment->amount,
            'method' => $payment->method,
            'reference' => $payment->reference,
            'notes' => $payment->notes,
            'createdAt' => $payment->created_at?->toISOString(),
            'updatedAt' => $payment->updated_at?->toISOString(),
        ];
    }
}
