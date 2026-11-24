<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Lead;
use App\Models\LeaseAllocation;
use App\Models\SafariAllocation;
use App\Models\Setting;
use App\Models\User;
use App\Support\PerDiemAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class JobCardController extends Controller
{
    private const TYPES = ['Safari', 'Safari - Daily', 'Safari - Monthly', 'Safari - Yearly', 'Long Term Lease', 'Test Drive', 'Service', 'Client Viewing', 'Others'];

    private const STATUSES = ['Open', 'Closed'];

    private const REASON_TYPES = ['Test Drive', 'Service', 'Others'];

    private const VEHICLE_RUN_TYPES = ['Test Drive', 'Service', 'Client Viewing', 'Others'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobCard::class);

        $query = JobCard::query()
            ->with(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract'])
            // The job-card list uses this count to show whether an allowance
            // has been pushed for approval, so pending/draft vouchers must be
            // included before they are confirmed as paid.
            ->withCount('perDiemVouchers as per_diem_vouchers_count')
            ->withCount(['confirmedPerDiemVouchers as paid_per_diem_vouchers_count' => fn ($query) => $query->where('payment_status', 'paid')])
            ->withCount(['safariAllocations as assigned_driver_count' => fn($query) => $query->whereNotNull('driver_id')])
            ->withMax('perDiemVouchers', 'updated_at')
            ->latest('id');

        if ($request->user()?->hasRole('Driver')) {
            $query->whereIn(
                'lead_id',
                SafariAllocation::query()
                    ->where('driver_id', $request->user()->id)
                    ->select('lead_id')
            );
        }

        $jobCards = $query->get();

        return response()->json([
            'message' => 'Job cards fetched successfully.',
            'jobCards' => $jobCards->map(fn(JobCard $jobCard): array => $this->transform($jobCard))->values(),
        ]);
    }

    public function show(JobCard $jobCard): JsonResponse
    {
        $this->authorize('view', $jobCard);

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card fetched successfully.',
            'jobCard' => $this->transformWithDriverProfiles($jobCard),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', JobCard::class);

        $validated = $request->validate($this->rules($request));

        $type = $validated['type'];
        if ($this->isStatusManagedJobCardType($type) && ($validated['status'] ?? 'Open') !== 'Open') {
            abort(403, 'Safari and Lease job cards must be opened or closed through the authorized status action.');
        }

        if ($this->isSafariType($type) && empty($validated['leadId'])) {
            return response()->json([
                'message' => 'leadId is required for Safari job cards.',
            ], 422);
        }

        if ($this->isSafariType($type) && ! empty($validated['leadId'])) {
            $duplicate = $this->findDuplicateSafariLeadJobCard((int) $validated['leadId']);
            if ($duplicate !== null) {
                return response()->json([
                    'message' => 'A Safari job card already exists for this lead (' . ($duplicate->job_card_no ?: ('ID ' . $duplicate->id)) . '). Update the existing job card instead of creating another one.',
                ], 422);
            }
        }

        if ($this->isLeaseType($type) && empty($validated['leaseAllocationId'])) {
            return response()->json([
                'message' => 'leaseAllocationId is required for Long Term Lease job cards.',
            ], 422);
        }

        if ($this->isLeaseType($type) && ! empty($validated['leaseAllocationId'])) {
            $duplicate = $this->findDuplicateLeaseAllocationJobCard((int) $validated['leaseAllocationId']);
            if ($duplicate !== null) {
                return response()->json([
                    'message' => 'A job card already exists for this lease allocation (' . ($duplicate->job_card_no ?: ('ID ' . $duplicate->id)) . ').',
                ], 422);
            }
        }

        $payload = $this->applyTypeSpecificPayload($validated, $type, null);

        $jobCard = JobCard::create($payload);

        $jobCard->update([
            'job_card_no' => $this->generateJobCardNo($jobCard->id),
        ]);

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card created successfully.',
            'jobCard' => $this->transform($jobCard),
        ], 201);
    }

    public function update(Request $request, JobCard $jobCard): JsonResponse
    {
        $this->authorize('update', $jobCard);

        if ($this->isStatusManagedJobCardType($jobCard->type) && $jobCard->status === 'Closed') {
            abort(403, 'Closed Safari and Lease job cards cannot be edited. Reopen the job card first.');
        }

        $validated = $request->validate($this->rules($request, isUpdate: true, jobCard: $jobCard));

        $type = $validated['type'] ?? $jobCard->type;
        if (
            ($this->isStatusManagedJobCardType($jobCard->type) || $this->isStatusManagedJobCardType($type)) &&
            ((array_key_exists('status', $validated) && $validated['status'] !== $jobCard->status) ||
                ($this->isStatusManagedJobCardType($type) && ! $this->isStatusManagedJobCardType($jobCard->type) && ($validated['status'] ?? $jobCard->status) === 'Closed'))
        ) {
            abort(403, 'Use the dedicated job card status action to open or close Safari and Lease job cards.');
        }

        $leadId = array_key_exists('leadId', $validated) ? $validated['leadId'] : $jobCard->lead_id;
        $vehicleId = array_key_exists('vehicleId', $validated) ? $validated['vehicleId'] : $jobCard->vehicle_id;
        $leaseContractId = array_key_exists('leaseContractId', $validated) ? $validated['leaseContractId'] : $jobCard->lease_contract_id;
        $leaseAllocationId = array_key_exists('leaseAllocationId', $validated) ? $validated['leaseAllocationId'] : $jobCard->lease_allocation_id;

        if ($this->isSafariType($type) && empty($leadId)) {
            return response()->json([
                'message' => 'leadId is required for Safari job cards.',
            ], 422);
        }

        if ($this->isSafariType($type) && ! empty($leadId)) {
            $duplicate = $this->findDuplicateSafariLeadJobCard((int) $leadId, (int) $jobCard->id);
            if ($duplicate !== null) {
                return response()->json([
                    'message' => 'A Safari job card already exists for this lead (' . ($duplicate->job_card_no ?: ('ID ' . $duplicate->id)) . '). Update the existing job card instead of creating another one.',
                ], 422);
            }
        }

        if ($this->isLeaseType($type) && empty($leaseAllocationId)) {
            return response()->json([
                'message' => 'leaseAllocationId is required for Long Term Lease job cards.',
            ], 422);
        }

        if ($this->isLeaseType($type) && ! empty($leaseAllocationId)) {
            $duplicate = $this->findDuplicateLeaseAllocationJobCard(
                (int) $leaseAllocationId,
                (int) $jobCard->id,
            );
            if ($duplicate !== null) {
                return response()->json([
                    'message' => 'A job card already exists for this lease allocation (' . ($duplicate->job_card_no ?: ('ID ' . $duplicate->id)) . ').',
                ], 422);
            }
        }

        $payload = $this->applyTypeSpecificPayload($validated, $type, $jobCard);
        $allowanceChanged = $this->allowancePayloadChanged($jobCard, $payload);

        DB::transaction(function () use ($jobCard, $payload, $allowanceChanged): void {
            if ($allowanceChanged) {
                $jobCard->load('perDiemVouchers.paymentLines');
                foreach ($jobCard->perDiemVouchers as $voucher) {
                    $this->assertAllowanceCanBeChanged($voucher);
                }
            }

            $jobCard->update($payload);

            if ($allowanceChanged) {
                foreach ($jobCard->perDiemVouchers as $voucher) {
                    $this->refreshVoucherAllowance($voucher, $payload['route_itinerary'] ?? []);
                }
            }
        });

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card updated successfully.',
            'jobCard' => $this->transform($jobCard),
        ]);
    }

    public function updateStatus(Request $request, JobCard $jobCard): JsonResponse
    {
        $allowedEmails = ['arif@sher.co.tz', 'tanveer@sher.co.tz'];
        abort_unless(in_array(strtolower(trim((string) $request->user()?->email)), $allowedEmails, true), 403);
        abort_unless($this->isStatusManagedJobCardType($jobCard->type), 422, 'Only Safari and Long Term Lease job cards can be opened or closed here.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'safariEndDate' => ['required_if:status,Closed', 'nullable', 'date'],
            'timeIn' => ['required_if:status,Closed', 'nullable', 'date_format:H:i'],
        ]);

        $jobCard->status = $validated['status'];
        if ($validated['status'] === 'Closed') {
            $jobCard->safari_end_date = $validated['safariEndDate'];
            $jobCard->time_in = $validated['timeIn'];
        }
        $jobCard->save();
        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        return response()->json([
            'message' => 'Job card status updated successfully.',
            'jobCard' => $this->transform($jobCard),
        ]);
    }

    public function destroy(JobCard $jobCard): JsonResponse
    {
        $this->authorize('delete', $jobCard);

        $jobCard->delete();

        return response()->json([
            'message' => 'Job card deleted successfully.',
        ]);
    }

    public function pdf(JobCard $jobCard): Response
    {
        $this->authorize('view', $jobCard);

        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.vehicle', 'leaseAllocation.driver', 'leaseAllocation.leaseContract']);

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'email' => Setting::get('company_email'),
            'phone' => Setting::get('company_phone'),
            'address' => Setting::get('company_address'),
            'tax_registration_number' => Setting::get('tax_registration_number'),
        ];

        $transformedJobCard = $this->transformWithDriverProfiles($jobCard);

        $pdf = Pdf::loadView('job-cards.pdf', [
            'jobCard' => $transformedJobCard,
            'company' => $company,
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'job-card-' . $jobCard->id . '.pdf';

        return $pdf->download($filename);
    }

    public function perDiem(Request $request, JobCard $jobCard): JsonResponse
    {
        $this->authorize('view', $jobCard);
        $validated = $request->validate([
            'driverKey' => ['required', 'string', 'max:100'],
            // Creating a per diem is an explicit workflow action. Require the
            // caller to opt in so job-card create/update/status requests can
            // never accidentally create a voucher.
            'pushForApproval' => ['required', 'boolean', 'accepted'],
        ]);
        $jobCard->load(['lead', 'vehicle', 'leaseContract', 'leaseAllocation.driver', 'leaseAllocation.vehicle']);
        $data = $this->transformWithDriverProfiles($jobCard);
        $driver = collect($data['driverProfiles'])->first(function ($profile) use ($validated) {
            $key = $profile['id'] ? (string) $profile['id'] : 'manual-' . hash('sha256', trim($profile['name']));
            return $key === $validated['driverKey'] && trim($profile['name']) !== '';
        });
        abort_unless($driver, 422, 'Select a driver assigned to this job card.');
        if ($request->user()->hasRole('Driver')) {
            abort_unless($driver['id'] === $request->user()->id, 403);
        }

        $allocatedVehicle = collect($data['allocatedVehicles'] ?? [])
            ->first(fn(array $allocation): bool => (int) ($allocation['driverProfile']['id'] ?? 0) === (int) ($driver['id'] ?? 0));
        $driverVehicles = $this->allocatedVehiclesForDriver($jobCard, $driver['id'] ?? null);
        $vehiclePlateNo = $driverVehicles->pluck('vehicle.plate_no')
            ->filter()
            ->unique()
            ->implode(', ');
        $vehiclePlateNo = $vehiclePlateNo !== ''
            ? $vehiclePlateNo
            : ($allocatedVehicle['plateNo']
                ?? ($jobCard->leaseAllocation?->vehicle?->plate_no ?? $jobCard->vehicle?->plate_no ?? ''));
        $clientName = $data['tourOperatorClientName']
            ?: ($jobCard->leaseAllocation?->leaseContract?->client_name
                ?? $jobCard->leaseContract?->client_name
                ?? $jobCard->lead?->client_company
                ?? '');

        $vehicleCount = max(1, $driverVehicles->count());
        $voucher = \Illuminate\Support\Facades\DB::transaction(function () use ($jobCard, $validated, $data, $driver, $request, $vehiclePlateNo, $clientName, $vehicleCount) {
            JobCard::whereKey($jobCard->id)->lockForUpdate()->firstOrFail();
            $existing = \App\Models\PerDiemVoucher::where('job_card_id', $jobCard->id)
                ->where('driver_key', $validated['driverKey'])
                ->lockForUpdate()
                ->first();
            if ($existing) {
                $this->assertAllowanceCanBeChanged($existing);
            }
            $items = $data['routeItinerary'];
            abort_if(empty($items), 422, 'Add itinerary lines and daily allowances to the job card first.');
            $driverContractType = trim((string) ($driver['contractType'] ?? ''));
            $contractAllowances = is_array($jobCard->driver_allowances) ? $jobCard->driver_allowances : [];
            $contractAllowance = $driverContractType !== '' ? ($contractAllowances[$driverContractType] ?? null) : null;
            $totalCents = 0;
            foreach ($items as &$item) {
                if ($contractAllowance !== null && is_numeric($contractAllowance)) {
                    $item['allowancePerDay'] = (float) $contractAllowance;
                }
                abort_if(
                    $item['allowancePerDay'] === null || $item['allowancePerDay'] < 0,
                    422,
                    'Enter a non-negative allowance for every itinerary line before generating the voucher.'
                );
                $cents = (int) round($item['allowancePerDay'] * $vehicleCount * 100);
                $item['allowancePerDay'] = $cents / 100;
                $totalCents += $cents;
            }
            unset($item);
            $snapshot = [
                'jobCardNo' => $data['jobCardNo'],
                'createdAt' => $jobCard->created_at?->format('d M Y'),
                'driverName' => $driver['name'],
                'vehiclePlateNo' => $vehiclePlateNo,
                'vehicleCount' => $vehicleCount,
                'clientName' => $clientName,
                'groupName' => $data['groupName'],
                'safariStartDate' => $jobCard->safari_start_date?->format('d M Y'),
                'safariEndDate' => $jobCard->safari_end_date?->format('d M Y'),
                'items' => $items,
                'total' => $totalCents / 100,
                'preparedBy' => $request->user()->name,
                'contractType' => $driver['contractType'] ?? '',
                'bankName' => $driver['bankName'] ?? '',
                'bankAccountNumber' => $driver['bankAccountNumber'] ?? '',
                'bankAccountName' => $driver['name'],
            ];

            if ($existing) {
                $snapshotChanged = $this->allowanceSnapshotChanged($existing->snapshot, $snapshot);
                if ($snapshotChanged && $existing->paymentLines()->exists()) {
                    $this->resetAllowanceApproval($existing);
                }
                $existing->update([
                    'snapshot' => $snapshot,
                    'requested_for_approval_at' => now(),
                ]);
                $this->syncPerDiemPaymentLines($existing->fresh());
                return $existing->fresh();
            }

            $voucher = \App\Models\PerDiemVoucher::create([
                'job_card_id' => $jobCard->id,
                'driver_key' => $validated['driverKey'],
                'snapshot' => $snapshot,
                'requested_for_approval_at' => now(),
            ]);
            $this->syncPerDiemPaymentLines($voucher);
            return $voucher->fresh();
        });
        return response()->json([
            'message' => 'Allowance scheduled for approval. The per diem PDF will be generated when payment is confirmed.',
            'perDiem' => $this->transformPerDiemVoucher($voucher->load('paymentLines', 'jobCard')),
        ]);
    }

    public function approvePerDiemPayments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paymentIds' => ['required', 'array', 'min:1'],
            'paymentIds.*' => ['integer', 'distinct', 'exists:per_diem_payment_lines,id'],
            'approvals' => ['sometimes', 'array'],
            'approvals.*.id' => ['required', 'integer', 'distinct'],
            'approvals.*.amount' => ['required', 'numeric', 'min:0.01'],
            'approvals.*.reason' => ['nullable', 'string', 'max:1000'],
            'approvals.*.paymentMode' => ['nullable', Rule::in(['advance', 'full'])],
            'rejectPaymentIds' => ['sometimes', 'array'],
            'rejectPaymentIds.*' => ['integer', 'distinct', 'exists:per_diem_payment_lines,id'],
        ]);

        $approved = DB::transaction(function () use ($validated, $request) {
            $payments = \App\Models\PerDiemPaymentLine::query()
                ->with('voucher.jobCard')
                ->whereIn('id', $validated['paymentIds'])
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                $voucher = $payment->voucher;
                $this->authorize('update', $voucher->jobCard);
                $snapshot = is_array($voucher->snapshot) ? $voucher->snapshot : [];
                $contractType = $this->perDiemContractType($voucher, $snapshot);
                if (blank($snapshot['bankAccountNumber'] ?? null) && ctype_digit((string) $voucher->driver_key)) {
                    $driver = User::query()->find((int) $voucher->driver_key);
                    $snapshot['bankName'] = $driver?->bank_name ?? '';
                    $snapshot['bankAccountNumber'] = $driver?->bank_account_number ?? '';
                    $snapshot['bankAccountName'] = $driver?->name ?? ($snapshot['driverName'] ?? '');
                    $voucher->snapshot = $snapshot;
                    $voucher->save();
                }
                abort_if(blank($snapshot['bankAccountNumber'] ?? null), 422, "Add bank account details for {$snapshot['driverName']} before approval.");
                abort_if($payment->status !== 'draft', 422, "Payment {$voucher->number()} is already approved or exported.");
                if (in_array($payment->id, $validated['rejectPaymentIds'] ?? [], true)) {
                    $payment->update(['status' => 'rejected', 'approval_reason' => 'Declined during allowance review.']);
                    continue;
                }
                $approval = collect($validated['approvals'] ?? [])->firstWhere('id', $payment->id);
                $mode = $approval['paymentMode'] ?? null;
                $approvedAmount = $approval ? round((float) $approval['amount'], 2) : round((float) $payment->amount, 2);
                abort_if($approvedAmount <= 0, 422, "Payment {$voucher->number()} has no payable amount.");
                $requestedAmount = round((float) ($payment->requested_amount ?? $payment->amount), 2);
                $totalAllowance = round((float) ($snapshot['total'] ?? 0), 2);
                if ($mode === 'advance') {
                    abort_if($payment->stage === 'balance' || str_starts_with($payment->stage, 'remainder-'), 422, 'A balance cannot be approved as an advance.');
                    abort_if($approvedAmount >= $totalAllowance, 422, 'An advance must be less than the full allowance.');
                } elseif ($mode === 'full') {
                    abort_if($approvedAmount > $totalAllowance, 422, "Approval exceeds the job card allowance value for {$voucher->number()}.");
                } else {
                    abort_if($approvedAmount > $requestedAmount, 422, "Approved amount cannot exceed the requested allowance for {$voucher->number()}.");
                }
                $alreadyApproved = (float) $voucher->paymentLines()->whereIn('status', ['approved', 'exported', 'paid'])->sum('amount');
                abort_if($alreadyApproved + $approvedAmount > $totalAllowance, 422, "Approval exceeds the job card allowance value for {$voucher->number()}.");
                if ($mode === 'full' && $payment->stage === 'advance') {
                    $payment->stage = 'full';
                } elseif ($mode === 'advance' && $payment->stage === 'full') {
                    $payment->stage = 'advance';
                }

                $voucher->update([
                    'payment_status' => 'approved',
                    'payment_reference' => $voucher->payment_reference ?: 'PAY-' . $voucher->number(),
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);
                $payment->update([
                    // Keep the original request for comparison on the approval
                    // and paid views; the approved amount belongs in `amount`.
                    'requested_amount' => $payment->requested_amount ?? $payment->amount,
                    'amount' => $approvedAmount,
                    'status' => 'approved',
                    'payment_reference' => 'PAY-' . $voucher->number() . '-' . strtoupper(substr($payment->stage, 0, 3)),
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'approval_reason' => $approval ? trim((string) ($approval['reason'] ?? '')) ?: null : null,
                ]);
                $remainder = $mode === 'advance' ? round($totalAllowance - $alreadyApproved - $approvedAmount, 2) : ($mode === 'full' ? 0 : round($requestedAmount - $approvedAmount, 2));
                if ($mode && $voucher->paymentLines()->where('stage', 'balance')->where('status', 'draft')->exists()) {
                    $balance = $voucher->paymentLines()->where('stage', 'balance')->where('status', 'draft')->first();
                    if ($remainder > 0) $balance->update(['amount' => $remainder, 'requested_amount' => $remainder]);
                    else $balance->update(['status' => 'rejected', 'approval_reason' => 'Superseded by full payment.']);
                    $remainder = 0;
                }
                if ($remainder > 0) {
                    \App\Models\PerDiemPaymentLine::create([
                        'per_diem_voucher_id' => $voucher->id,
                        'stage' => $mode === 'advance' ? 'balance' : 'remainder-' . $payment->id,
                        'amount' => $remainder,
                        'requested_amount' => $remainder,
                        'status' => 'draft',
                    ]);
                }
            }

            return $payments->fresh('voucher');
        });

        return response()->json([
            'message' => 'Per diem payments approved successfully.',
            'payments' => $approved->map(fn ($payment) => $this->transformPerDiemPayment($payment))->values(),
        ]);
    }

    public function markPerDiemPaid(Request $request, \App\Models\PerDiemPaymentLine $payment): JsonResponse
    {
        $this->authorize('update', $payment->voucher->jobCard);
        abort_if(!in_array($payment->status, ['approved', 'exported'], true), 422, 'Only approved payments can be marked as paid.');
        $validated = $request->validate([
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'paymentProof' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $path = $request->file('paymentProof')->store('per-diem-payment-proofs', 'public');
        DB::transaction(function () use ($payment, $validated, $path, $request): void {
            $payment->update([
                'status' => 'paid',
                'payment_reference' => trim((string) ($validated['paymentReference'] ?? '')) ?: $payment->payment_reference,
                'payment_proof_path' => $path,
                'paid_by' => $request->user()->id,
                'paid_at' => now(),
            ]);
            $this->storeConfirmedPerDiem($payment->voucher);
        });

        return response()->json([
            'message' => 'Payment proof uploaded and allowance marked as paid.',
            'payment' => $this->transformPerDiemPayment($payment->fresh('voucher')),
        ]);
    }

    public function markPerDiemBatchPaid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paymentIds' => ['required', 'array', 'min:1'],
            'paymentIds.*' => ['integer', 'distinct', 'exists:per_diem_payment_lines,id'],
            'paymentReference' => ['nullable', 'string', 'max:90'],
            'paymentProof' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $paid = DB::transaction(function () use ($validated, $request) {
            $payments = \App\Models\PerDiemPaymentLine::query()
                ->with('voucher.jobCard')
                ->whereIn('id', $validated['paymentIds'])
                ->lockForUpdate()
                ->get();

            abort_if($payments->count() !== count($validated['paymentIds']), 422, 'One or more selected payments no longer exist.');
            foreach ($payments as $payment) {
                $this->authorize('update', $payment->voucher->jobCard);
                abort_if($payment->status !== 'exported', 422, "Only payments with a bank file can be marked paid. {$payment->voucher->number()} is {$payment->status}.");
            }

            $proofPath = $request->hasFile('paymentProof')
                ? $request->file('paymentProof')->store('per-diem-payment-proofs', 'public')
                : null;
            $reference = trim((string) ($validated['paymentReference'] ?? ''));
            foreach ($payments as $payment) {
                $payment->update([
                    'status' => 'paid',
                    'payment_reference' => $reference !== '' ? $reference . '-' . $payment->id : $payment->payment_reference,
                    'payment_proof_path' => $proofPath,
                    'paid_by' => $request->user()->id,
                    'paid_at' => now(),
                ]);
            }

            foreach ($payments->pluck('voucher')->unique('id') as $voucher) {
                $this->storeConfirmedPerDiem($voucher);
            }

            return $payments->count();
        });

        return response()->json(['message' => "{$paid} payment(s) marked as paid.", 'paidCount' => $paid]);
    }

    public function updatePerDiemPayment(Request $request, \App\Models\PerDiemPaymentLine $payment): JsonResponse
    {
        $payment->load('voucher.jobCard', 'voucher.paymentLines');
        $this->authorize('update', $payment->voucher->jobCard);
        abort_if($payment->status === 'paid', 422, 'Paid payments cannot be edited.');
        abort_unless(in_array($payment->status, ['approved', 'exported'], true), 422, 'Only unpaid payments can be edited.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paymentReference' => ['nullable', 'string', 'max:100'],
            'approvalReason' => ['nullable', 'string', 'max:1000'],
        ]);
        $snapshot = is_array($payment->voucher->snapshot) ? $payment->voucher->snapshot : [];
        $totalAllowance = round((float) ($snapshot['total'] ?? 0), 2);
        $otherApproved = (float) $payment->voucher->paymentLines
            ->whereIn('status', ['approved', 'exported', 'paid'])
            ->where('id', '!=', $payment->id)
            ->sum('amount');
        $amount = round((float) $validated['amount'], 2);
        abort_if($otherApproved + $amount > $totalAllowance, 422, 'The edited amount exceeds the job card allowance value.');

        $payment->update([
            'amount' => $amount,
            'payment_reference' => trim((string) ($validated['paymentReference'] ?? '')) ?: $payment->payment_reference,
            'approval_reason' => trim((string) ($validated['approvalReason'] ?? '')) ?: null,
        ]);

        return response()->json([
            'message' => 'Payment record updated successfully.',
            'payment' => $this->transformPerDiemPayment($payment->fresh('voucher')),
        ]);
    }

    public function confirmPerDiemBankUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paymentIds' => ['required', 'array', 'min:1'],
            'paymentIds.*' => ['integer', 'distinct', 'exists:per_diem_payment_lines,id'],
        ]);

        $confirmed = DB::transaction(function () use ($validated) {
            $payments = \App\Models\PerDiemPaymentLine::query()
                ->with('voucher.jobCard')
                ->whereIn('id', $validated['paymentIds'])
                ->lockForUpdate()
                ->get();

            abort_if($payments->count() !== count($validated['paymentIds']), 422, 'One or more selected payments no longer exist.');
            foreach ($payments as $payment) {
                $this->authorize('update', $payment->voucher->jobCard);
                abort_if($payment->status !== 'approved', 422, "Only approved payments can be confirmed as uploaded. {$payment->voucher->number()} is {$payment->status}.");
            }

            $batchReference = 'BANK-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
            $payments->each(fn ($payment) => $payment->update([
                'status' => 'exported',
                'payment_batch_reference' => $batchReference,
                'exported_at' => now(),
            ]));

            return $payments->count();
        });

        return response()->json([
            'message' => "{$confirmed} payment(s) confirmed as uploaded on the bank side.",
            'confirmedCount' => $confirmed,
        ]);
    }

    public function updatePerDiemDeduction(Request $request, \App\Models\PerDiemPaymentLine $payment): JsonResponse
    {
        $this->authorize('update', $payment->voucher->jobCard);
        abort_unless($payment->stage === 'balance', 422, 'Only the freelance balance can have a return deduction.');
        abort_unless($payment->voucher->jobCard->status === 'Closed', 422, 'Close the job card before entering the final deduction.');
        abort_if($payment->status !== 'draft', 422, 'This payment is already approved or exported.');

        $validated = $request->validate([
            'fuelDeduction' => ['required', 'numeric', 'min:0'],
            'deductionReason' => ['required', 'string', 'max:1000'],
        ]);
        $snapshot = is_array($payment->voucher->snapshot) ? $payment->voucher->snapshot : [];
        $baseBalance = round((float) ($snapshot['total'] ?? 0) - round((float) ($snapshot['total'] ?? 0) / 2, 2), 2);
        $deduction = round((float) $validated['fuelDeduction'], 2);
        abort_if($deduction > $baseBalance, 422, 'The deduction cannot exceed the freelance balance.');
        $payment->update([
            'amount' => round($baseBalance - $deduction, 2),
            'fuel_deduction' => $deduction,
            'deduction_reason' => trim($validated['deductionReason']),
        ]);

        return response()->json(['payment' => $this->transformPerDiemPayment($payment->fresh('voucher'))]);
    }

    public function exportPerDiemBankFile(Request $request): Response
    {
        $validated = $request->validate([
            'paymentIds' => ['required', 'array', 'min:1'],
            'paymentIds.*' => ['integer', 'distinct', 'exists:per_diem_payment_lines,id'],
        ]);

        $result = DB::transaction(function () use ($validated) {
            $payments = \App\Models\PerDiemPaymentLine::query()
                ->with('voucher.jobCard')
                ->whereIn('id', $validated['paymentIds'])
                ->lockForUpdate()
                ->get()
                ->sortBy('id')
                ->values();

            abort_if($payments->isEmpty(), 422, 'Select at least one payment for the bank file.');
            foreach ($payments as $payment) {
                $this->authorize('update', $payment->voucher->jobCard);
                $contractType = $this->perDiemContractType($payment->voucher, $payment->voucher->snapshot ?: []);
                abort_if($payment->status !== 'approved', 422, "Only approved payments can be exported. {$payment->voucher->number()} is {$payment->status}.");
            }

            $batchReference = 'BANK-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
            $payments->each(fn ($payment) => $payment->update([
                'status' => 'exported',
                'payment_batch_reference' => $batchReference,
                'exported_at' => now(),
            ]));

            return compact('payments', 'batchReference');
        });

        $rows = [[
            'payment_reference', 'beneficiary_name', 'bank_name', 'account_number',
            'amount', 'currency', 'narration',
        ]];
        foreach ($result['payments'] as $payment) {
            $voucher = $payment->voucher;
            $snapshot = is_array($voucher->snapshot) ? $voucher->snapshot : [];
            $rows[] = [
                $payment->payment_reference,
                $snapshot['bankAccountName'] ?? $snapshot['driverName'] ?? '',
                $snapshot['bankName'] ?? '',
                $snapshot['bankAccountNumber'] ?? '',
                number_format((float) $payment->amount, 2, '.', ''),
                'TZS',
                'Per diem ' . $voucher->number() . ' ' . ucfirst($payment->stage),
            ];
        }

        $csv = collect($rows)->map(fn (array $row) => collect($row)->map(function ($value) {
            return '"' . str_replace('"', '""', (string) $value) . '"';
        })->implode(','))->implode("\r\n") . "\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $result['batchReference'] . '.csv"',
            'X-Payment-Batch-Reference' => $result['batchReference'],
        ]);
    }

    public function perDiemIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JobCard::class);
        $request->validate([
            'jobCardId' => ['nullable', 'integer', 'min:1'],
            'awaitingApproval' => ['nullable', 'boolean'],
            'confirmedOnly' => ['nullable', 'boolean'],
        ]);
        $query = \App\Models\PerDiemVoucher::query()->latest('id');
        if ($request->filled('jobCardId')) {
            $query->where('job_card_id', $request->integer('jobCardId'));
        }
        if ($request->boolean('awaitingApproval')) {
            $query->whereNotNull('requested_for_approval_at')
                ->whereHas('paymentLines', fn ($lineQuery) => $lineQuery->where('status', 'draft'));
        }
        if ($request->boolean('confirmedOnly')) {
            $query->whereNotNull('confirmed_at');
        }
        if ($request->user()->hasRole('Driver')) {
            $query->where('driver_key', (string) $request->user()->id)
                ->whereHas('jobCard', fn($q) => $q->whereIn(
                    'lead_id',
                    SafariAllocation::where('driver_id', $request->user()->id)->select('lead_id')
                ));
        }
        $vouchers = $query->with(['paymentLines', 'jobCard'])->get();
        $vouchers->each(fn ($voucher) => $this->syncPerDiemPaymentLines($voucher));
        return response()->json(['perDiems' => $vouchers->load('paymentLines')->map(fn($voucher) => $this->transformPerDiemVoucher($voucher))]);
    }

    private function syncPerDiemPaymentLines($voucher): void
    {
        if ($voucher->paymentLines()->exists()) return;
        $snapshot = is_array($voucher->snapshot) ? $voucher->snapshot : [];
        $contractType = $this->perDiemContractType($voucher, $snapshot);
        if (($snapshot['contractType'] ?? '') !== ($contractType === 'freelance' ? 'Freelance' : ($snapshot['contractType'] ?? ''))) {
            $snapshot['contractType'] = $contractType === 'freelance' ? 'Freelance' : ($snapshot['contractType'] ?? '');
            $voucher->snapshot = $snapshot;
            $voucher->save();
        }
        $total = round((float) ($snapshot['total'] ?? 0), 2);
        $status = $voucher->payment_status ?: 'draft';
        $stages = $contractType === 'freelance'
            ? ['advance' => round($total / 2, 2), 'balance' => round($total - round($total / 2, 2), 2)]
            : ['full' => $total];

        foreach ($stages as $stage => $amount) {
            \App\Models\PerDiemPaymentLine::firstOrCreate(
                ['per_diem_voucher_id' => $voucher->id, 'stage' => $stage],
                [
                    'amount' => $amount,
                    'requested_amount' => $amount,
                    'status' => $status === 'exported' ? 'exported' : $status,
                    'payment_reference' => $voucher->payment_reference ? $voucher->payment_reference . '-' . strtoupper(substr($stage, 0, 3)) : null,
                    'payment_batch_reference' => $voucher->payment_batch_reference,
                    'approved_at' => $voucher->approved_at,
                    'exported_at' => $voucher->exported_at,
                ],
            );
        }
    }

    private function assertAllowanceCanBeChanged($voucher): void
    {
        $voucher->loadMissing('paymentLines');
        $locked = $voucher->paymentLines->contains(fn ($line): bool => in_array($line->status, ['exported', 'paid'], true))
            || in_array($voucher->payment_status, ['exported', 'paid'], true)
            || $voucher->exported_at !== null
            || $voucher->paid_at !== null
            || $voucher->confirmed_at !== null;

        abort_if($locked, 422, 'This allowance cannot be changed because payment has already been uploaded or marked as paid.');
    }

    private function resetAllowanceApproval($voucher): void
    {
        $this->assertAllowanceCanBeChanged($voucher);
        $voucher->paymentLines()->delete();
        $voucher->update([
            'payment_status' => 'draft',
            'payment_reference' => null,
            'payment_batch_reference' => null,
            'approved_by' => null,
            'approved_at' => null,
            'exported_at' => null,
            'paid_at' => null,
            'confirmed_at' => null,
        ]);
    }

    private function refreshVoucherAllowance($voucher, array $routeItinerary): void
    {
        $this->resetAllowanceApproval($voucher);

        $snapshot = is_array($voucher->snapshot) ? $voucher->snapshot : [];
        $items = $routeItinerary;
        $contractType = trim((string) ($snapshot['contractType'] ?? ''));
        $contractAllowances = is_array($voucher->jobCard?->driver_allowances)
            ? $voucher->jobCard->driver_allowances
            : [];
        $contractAllowance = $contractType !== '' ? ($contractAllowances[$contractType] ?? null) : null;
        $vehicleCount = max(1, $this->allocatedVehiclesForDriver($voucher->jobCard, $voucher->driver_key)->count());
        $totalCents = 0;

        foreach ($items as &$item) {
            if ($contractAllowance !== null && is_numeric($contractAllowance)) {
                $item['allowancePerDay'] = (float) $contractAllowance;
            }
            $amountCents = (int) round((float) ($item['allowancePerDay'] ?? 0) * $vehicleCount * 100);
            $item['allowancePerDay'] = $amountCents / 100;
            $totalCents += $amountCents;
        }
        unset($item);

        $snapshot['items'] = $items;
        $snapshot['total'] = $totalCents / 100;
        $snapshot['vehicleCount'] = $vehicleCount;
        $voucher->update([
            'snapshot' => $snapshot,
            'requested_for_approval_at' => now(),
        ]);
        $this->syncPerDiemPaymentLines($voucher->fresh());
    }

    private function allowanceSnapshotChanged(?array $before, array $after): bool
    {
        return $this->allowanceValues($before['items'] ?? [])
            !== $this->allowanceValues($after['items'] ?? []);
    }

    private function allowancePayloadChanged(JobCard $jobCard, array $payload): bool
    {
        return $this->allowanceValues($jobCard->route_itinerary ?? [])
            !== $this->allowanceValues($payload['route_itinerary'] ?? [])
            || (float) ($jobCard->driver_allowance ?? 0) !== (float) ($payload['driver_allowance'] ?? 0)
            || json_encode($jobCard->driver_allowances ?? [], JSON_THROW_ON_ERROR)
                !== json_encode($payload['driver_allowances'] ?? [], JSON_THROW_ON_ERROR);
    }

    private function allowanceValues(array $items): array
    {
        return array_values(array_map(
            fn (array $item): ?float => ($item['allowancePerDay'] ?? $item['allowance_per_day'] ?? null) === null
                ? null
                : round((float) ($item['allowancePerDay'] ?? $item['allowance_per_day']), 2),
            $items,
        ));
    }

    private function perDiemContractType($voucher, array $snapshot): string
    {
        $contractType = strtolower(trim((string) ($snapshot['contractType'] ?? '')));
        if ($contractType === '' && ctype_digit((string) $voucher->driver_key)) {
            $contractType = strtolower(trim((string) User::query()->whereKey((int) $voucher->driver_key)->value('contract_type')));
        }
        return $contractType;
    }

    private function allocatedVehiclesForDriver(JobCard $jobCard, $driverId)
    {
        if (!$jobCard->lead_id || !$driverId) {
            return collect();
        }

        return SafariAllocation::query()
            ->with('vehicle')
            ->where('lead_id', $jobCard->lead_id)
            ->where('driver_id', (int) $driverId)
            ->get()
            ->unique('vehicle_id')
            ->values();
    }

    private function transformPerDiemVoucher($voucher): array
    {
        return [
            'id' => $voucher->id,
            'number' => $voucher->number(),
            'jobCardId' => $voucher->job_card_id,
            'jobCardStatus' => $voucher->jobCard?->status,
            'generatedAt' => $voucher->created_at->toISOString(),
            'updatedAt' => $voucher->updated_at->toISOString(),
            'requestedForApprovalAt' => $voucher->requested_for_approval_at?->toISOString(),
            'driverKey' => $voucher->driver_key,
            'paymentStatus' => $voucher->payment_status ?: 'draft',
            'paymentReference' => $voucher->payment_reference,
            'paymentBatchReference' => $voucher->payment_batch_reference,
            'approvedAt' => $voucher->approved_at?->toISOString(),
            'exportedAt' => $voucher->exported_at?->toISOString(),
            'confirmedAt' => $voucher->confirmed_at?->toISOString(),
            'payments' => $this->visiblePerDiemPayments($voucher),
            ...($voucher->snapshot ?: []),
        ];
    }

    private function visiblePerDiemPayments($voucher): array
    {
        $snapshot = is_array($voucher->snapshot) ? $voucher->snapshot : [];
        $contractType = $this->perDiemContractType($voucher, $snapshot);
        return $voucher->paymentLines
            ->map(fn ($payment) => $this->transformPerDiemPayment($payment))
            ->values()
            ->all();
    }

    private function transformPerDiemPayment($payment): array
    {
        return [
            'id' => $payment->id,
            'stage' => $payment->stage,
            'amount' => (float) $payment->amount,
            'requestedAmount' => $payment->requested_amount !== null ? (float) $payment->requested_amount : (float) $payment->amount,
            'fuelDeduction' => (float) $payment->fuel_deduction,
            'deductionReason' => $payment->deduction_reason,
            'approvalReason' => $payment->approval_reason,
            'status' => $payment->status,
            'paymentReference' => $payment->payment_reference,
            'paymentBatchReference' => $payment->payment_batch_reference,
            'approvedAt' => $payment->approved_at?->toISOString(),
            'exportedAt' => $payment->exported_at?->toISOString(),
            'paidAt' => $payment->paid_at?->toISOString(),
            'paymentProofPath' => $payment->payment_proof_path,
        ];
    }

    public function perDiemPdf(Request $request, \App\Models\PerDiemVoucher $voucher): Response
    {
        $this->authorize('view', $voucher->jobCard);
        if ($request->user()->hasRole('Driver')) {
            abort_unless($voucher->driver_key === (string) $request->user()->id, 403);
        }
        abort_unless($voucher->paymentLines()->where('status', 'paid')->exists(), 422, 'The per diem PDF is available after payment is confirmed.');
        abort_unless($voucher->confirmed_at, 422, 'The per diem voucher is created after payment is confirmed.');
        $pdf = $this->renderPerDiem($voucher);
        Storage::disk('local')->put('per-diem-vouchers/' . $voucher->number() . '.pdf', $pdf->getContent());
        return $pdf;
    }

    private function storeConfirmedPerDiem(\App\Models\PerDiemVoucher $voucher): void
    {
        $voucher->load('paymentLines');
        $activePayments = $voucher->paymentLines->reject(fn ($line) => $line->status === 'rejected');
        if ($activePayments->isNotEmpty() && $activePayments->every(fn ($line) => $line->status === 'paid')) {
            $voucher->update([
                'payment_status' => 'paid',
                'paid_at' => $voucher->paid_at ?: now(),
                'confirmed_at' => $voucher->confirmed_at ?: now(),
            ]);
        }

        $pdf = $this->renderPerDiem($voucher->fresh());
        $path = 'per-diem-vouchers/' . $voucher->number() . '.pdf';
        abort_unless(Storage::disk('local')->put($path, $pdf->getContent()), 500, 'Unable to generate the confirmed per diem PDF.');
    }

    private function renderPerDiem(\App\Models\PerDiemVoucher $voucher): Response
    {
        $voucherData = $voucher->snapshot;
        $payments = $voucher->paymentLines()->where('status', '!=', 'rejected')->orderBy('id')->get();
        $paidPayments = $payments->where('status', 'paid');
        $approvedCents = $payments->whereIn('status', ['approved', 'exported', 'paid'])
            ->sum(fn ($payment): int => (int) round((float) $payment->amount * 100));
        $voucherData['items'] = PerDiemAllocation::acrossDates($voucherData['items'] ?? [], $approvedCents);
        $voucherData['total'] = $approvedCents / 100;
        $voucherData['paidAmount'] = (float) $paidPayments->sum('amount');
        $voucherData['payments'] = $payments->map(fn ($payment) => [
            'stage' => $payment->stage,
            'amount' => (float) $payment->amount,
            'status' => $payment->status,
            'reference' => $payment->payment_reference,
            'paidAt' => $payment->paid_at?->format('d M Y'),
        ])->all();
        $voucherData['paymentStatus'] = $paidPayments->isNotEmpty() && $paidPayments->count() < $payments->count()
            ? 'partially paid'
            : ($voucher->payment_status ?: 'draft');
        $voucherData['paymentReference'] = $voucher->payment_reference;
        $voucherData['paymentBatchReference'] = $voucher->payment_batch_reference;
        $jobCard = $voucher->jobCard()->with([
            'lead',
            'vehicle',
            'leaseContract',
            'leaseAllocation.vehicle',
            'leaseAllocation.leaseContract',
        ])->first();

        if (empty($voucherData['vehiclePlateNo']) && $jobCard) {
            $vehicle = $jobCard->leaseAllocation?->vehicle ?? $jobCard->vehicle;
            if ($jobCard->lead_id && ctype_digit((string) $voucher->driver_key)) {
                $vehicle = SafariAllocation::query()
                    ->with('vehicle')
                    ->where('lead_id', $jobCard->lead_id)
                    ->where('driver_id', (int) $voucher->driver_key)
                    ->first()?->vehicle ?? $vehicle;
            }
            $voucherData['vehiclePlateNo'] = $vehicle?->plate_no ?? '';
        }

        if (empty($voucherData['clientName']) && $jobCard) {
            $voucherData['clientName'] = $jobCard->tour_operator_client_name
                ?: ($jobCard->lead?->client_company
                    ?? $jobCard->leaseAllocation?->leaseContract?->client_name
                    ?? $jobCard->leaseContract?->client_name
                    ?? '');
        }

        $number = $voucher->number();
        return Pdf::loadView('job-cards.per-diem', [
            'voucher' => $voucherData,
            'number' => $number,
            'company' => [
                'name' => Setting::get('company_name', config('app.name')),
                'address' => Setting::get('company_address'),
                'phone' => Setting::get('company_phone'),
                'email' => Setting::get('company_email'),
            ],
            'logoDataUri' => $this->resolveLogoDataUri(),
        ])->setPaper('a5', 'portrait')->download($number . '.pdf');
    }

    private function driverGuideProfile($driver, $vehicle): array
    {
        return [
            'id' => $driver?->id,
            'name' => $driver?->name ?? '',
            'phone' => $driver?->phone ?? '',
            'languages' => $driver?->languages_spoken ?? '',
            'driverLicense' => $driver?->driver_license ?? '',
            'guideLicense' => $driver?->tour_guide_license ?? '',
            'contractType' => $driver?->contract_type ?? '',
            'bankName' => $driver?->bank_name ?? '',
            'bankAccountNumber' => $driver?->bank_account_number ?? '',
            'plateNo' => $vehicle?->plate_no ?? '',
        ];
    }

    private function transformWithDriverProfiles(JobCard $jobCard): array
    {
        $result = $this->transform($jobCard);

        if ($this->isSafariType($jobCard->type) && $jobCard->lead_id) {
            $allocations = SafariAllocation::query()
                ->with(['vehicle', 'driver'])
                ->where('lead_id', $jobCard->lead_id)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get();

            $result['allocatedVehicles'] = $allocations
                ->map(function (SafariAllocation $allocation): array {
                    return [
                        'vehicleNo' => (string) ($allocation->vehicle?->vehicle_no ?? ''),
                        'plateNo' => (string) ($allocation->vehicle?->plate_no ?? ''),
                        'driverName' => (string) ($allocation->driver?->name ?? ''),
                        'driverProfile' => $this->driverGuideProfile($allocation->driver, $allocation->vehicle),
                    ];
                })
                ->filter(fn(array $item): bool => trim($item['vehicleNo']) !== '' || trim($item['plateNo']) !== '' || trim($item['driverName']) !== '')
                ->unique(fn(array $item): string => implode('|', [
                    strtoupper(trim($item['vehicleNo'])),
                    strtoupper(trim($item['plateNo'])),
                    strtoupper(trim($item['driverName'])),
                ]))
                ->values()
                ->all();
        }

        if (!empty($result['allocatedVehicles'])) {
            $result['driverProfiles'] = collect(array_column($result['allocatedVehicles'], 'driverProfile'))
                ->unique(fn(array $profile): string => $profile['id']
                    ? 'id:' . $profile['id']
                    : 'name:' . strtolower(trim($profile['name'] ?? '')))
                ->values()
                ->all();
        } else {
            $vehicle = $jobCard->leaseAllocation?->vehicle ?? $jobCard->vehicle;
            $driver = $jobCard->leaseAllocation?->driver;
            if (!$driver && $vehicle) {
                $vehicle->loadMissing('assignedDriver');
                $driver = $vehicle->assignedDriver;
            }
            $profile = $this->driverGuideProfile($driver, $vehicle);
            // Preserve manually entered driver details when no linked driver exists.
            if (!$driver) {
                $profile['name'] = $jobCard->driver_details ?? '';
                $profile['languages'] = $jobCard->guide_language ?? '';
            }
            $result['driverProfiles'] = [$profile];
        }

        foreach ($result['driverProfiles'] as &$profile) {
            $profile['key'] = $profile['id'] ? (string) $profile['id'] : 'manual-' . hash('sha256', trim($profile['name']));
        }
        unset($profile);
        return $result;
    }

    private function generateJobCardNo(int $id): string
    {
        return 'JC-' . now()->format('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, bool $isUpdate = false, ?JobCard $jobCard = null): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';
        $requestedType = $request->input('type');
        $effectiveType = is_string($requestedType) && $requestedType !== '' ? $requestedType : $jobCard?->type;

        $leadIdRules = ['sometimes', 'nullable'];

        if ($this->isSafariType($effectiveType)) {
            if (! $isUpdate) {
                $leadIdRules[] = 'required';
            }

            $leadIdRules[] = 'integer';
            $leadIdRules[] = 'exists:leads,id';
        }

        return [
            'leadId' => $leadIdRules,
            'vehicleId' => ['sometimes', 'nullable', 'integer', 'exists:vehicles,id'],
            'leaseContractId' => ['sometimes', 'nullable', 'integer', 'exists:lease_contracts,id'],
            'leaseAllocationId' => ['sometimes', 'nullable', 'integer', 'exists:lease_allocations,id'],
            'type' => [$required, 'string', Rule::in(self::TYPES)],
            'status' => ['sometimes', 'string', Rule::in(self::STATUSES)],
            'safariStartDate' => ['sometimes', 'nullable', 'date'],
            'safariEndDate' => ['sometimes', 'nullable', 'date'],
            'timeOut' => ['sometimes', 'nullable', 'date_format:H:i'],
            'timeIn' => ['sometimes', 'nullable', 'date_format:H:i'],
            'routeSummary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'routeItinerary' => ['sometimes', 'nullable', 'array'],
            'routeItinerary.*.date' => ['sometimes', 'nullable', 'string', 'max:100'],
            'routeItinerary.*.dayDescription' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'routeItinerary.*.allowancePerDay' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'routePoints' => ['sometimes', 'nullable', 'array', 'max:100'],
            'routePoints.*.lat' => ['required_with:routePoints', 'numeric', 'between:-90,90'],
            'routePoints.*.lng' => ['required_with:routePoints', 'numeric', 'between:-180,180'],
            'routePoints.*.label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'routeDistanceKm' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.9'],
            'additionalDistanceKm' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.9'],
            'additionalDistanceNote' => ['sometimes', 'nullable', 'string', 'max:500'],
            'additionalDetails' => ['sometimes', 'nullable', 'string'],
            'numberOfDays' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:365'],
            'pickupLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dropoffLocation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bookingReferenceNo' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tourOperatorClientName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contactPerson' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contactNumber' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contactEmail' => ['sometimes', 'nullable', 'email', 'max:255'],
            'adults' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'children' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:120'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'clientDetails' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'kms' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'odometerOut' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'odometerIn' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mileage' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'fuelGaugeOut' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'fuelGaugeIn' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'approximateFuelUsed' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'driverDetails' => ['sometimes', 'nullable', 'string', 'max:255'],
            'driverAllowance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'driverAllowances' => ['sometimes', 'nullable', 'array'],
            'driverAllowances.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function applyTypeSpecificPayload(array $validated, string $type, ?JobCard $jobCard): array
    {
        $payload = [
            'lead_id' => array_key_exists('leadId', $validated) ? $validated['leadId'] : $jobCard?->lead_id,
            'vehicle_id' => array_key_exists('vehicleId', $validated) ? $validated['vehicleId'] : $jobCard?->vehicle_id,
            'lease_contract_id' => array_key_exists('leaseContractId', $validated) ? $validated['leaseContractId'] : $jobCard?->lease_contract_id,
            'lease_allocation_id' => array_key_exists('leaseAllocationId', $validated) ? $validated['leaseAllocationId'] : $jobCard?->lease_allocation_id,
            'type' => $type,
            'status' => array_key_exists('status', $validated) ? $validated['status'] : ($jobCard?->status ?? 'Open'),
            'safari_start_date' => array_key_exists('safariStartDate', $validated) ? $validated['safariStartDate'] : optional($jobCard?->safari_start_date)->format('Y-m-d'),
            'safari_end_date' => array_key_exists('safariEndDate', $validated) ? $validated['safariEndDate'] : optional($jobCard?->safari_end_date)->format('Y-m-d'),
            'time_out' => array_key_exists('timeOut', $validated) ? $validated['timeOut'] : ($jobCard?->time_out ? date('H:i', strtotime((string) $jobCard->time_out)) : null),
            'time_in' => array_key_exists('timeIn', $validated) ? $validated['timeIn'] : ($jobCard?->time_in ? date('H:i', strtotime((string) $jobCard->time_in)) : null),
            'route_summary' => array_key_exists('routeSummary', $validated) ? $validated['routeSummary'] : $jobCard?->route_summary,
            'route_points' => array_key_exists('routePoints', $validated) ? $validated['routePoints'] : $jobCard?->route_points,
            'route_distance_km' => array_key_exists('routeDistanceKm', $validated) ? $validated['routeDistanceKm'] : $jobCard?->route_distance_km,
            'additional_distance_km' => array_key_exists('additionalDistanceKm', $validated) ? $validated['additionalDistanceKm'] : $jobCard?->additional_distance_km,
            'additional_distance_note' => array_key_exists('additionalDistanceNote', $validated) ? $validated['additionalDistanceNote'] : $jobCard?->additional_distance_note,
            'additional_details' => array_key_exists('additionalDetails', $validated) ? $validated['additionalDetails'] : $jobCard?->additional_details,
            'number_of_days' => array_key_exists('numberOfDays', $validated) ? $validated['numberOfDays'] : $jobCard?->number_of_days,
            'pickup_location' => array_key_exists('pickupLocation', $validated) ? $validated['pickupLocation'] : $jobCard?->pickup_location,
            'dropoff_location' => array_key_exists('dropoffLocation', $validated) ? $validated['dropoffLocation'] : $jobCard?->dropoff_location,
            'booking_reference_no' => array_key_exists('bookingReferenceNo', $validated) ? $validated['bookingReferenceNo'] : $jobCard?->booking_reference_no,
            'tour_operator_client_name' => array_key_exists('tourOperatorClientName', $validated) ? $validated['tourOperatorClientName'] : $jobCard?->tour_operator_client_name,
            'contact_person' => array_key_exists('contactPerson', $validated) ? $validated['contactPerson'] : $jobCard?->contact_person,
            'contact_number' => array_key_exists('contactNumber', $validated) ? $validated['contactNumber'] : $jobCard?->contact_number,
            'contact_email' => array_key_exists('contactEmail', $validated) ? $validated['contactEmail'] : $jobCard?->contact_email,
            'adults' => array_key_exists('adults', $validated) ? $validated['adults'] : $jobCard?->adults,
            'children' => array_key_exists('children', $validated) ? $validated['children'] : $jobCard?->children,
            'nationality' => array_key_exists('nationality', $validated) ? $validated['nationality'] : $jobCard?->nationality,
            'reason' => array_key_exists('reason', $validated) ? $validated['reason'] : $jobCard?->reason,
            'client_details' => array_key_exists('clientDetails', $validated) ? $validated['clientDetails'] : $jobCard?->client_details,
            'location' => array_key_exists('location', $validated) ? $validated['location'] : $jobCard?->location,
            'kms' => array_key_exists('kms', $validated) ? $validated['kms'] : $jobCard?->kms,
            'odometer_out' => array_key_exists('odometerOut', $validated) ? $validated['odometerOut'] : $jobCard?->odometer_out,
            'odometer_in' => array_key_exists('odometerIn', $validated) ? $validated['odometerIn'] : $jobCard?->odometer_in,
            'mileage' => array_key_exists('mileage', $validated) ? $validated['mileage'] : $jobCard?->mileage,
            'fuel_gauge_out' => array_key_exists('fuelGaugeOut', $validated) ? $validated['fuelGaugeOut'] : $jobCard?->fuel_gauge_out,
            'fuel_gauge_in' => array_key_exists('fuelGaugeIn', $validated) ? $validated['fuelGaugeIn'] : $jobCard?->fuel_gauge_in,
            'approximate_fuel_used' => array_key_exists('approximateFuelUsed', $validated) ? $validated['approximateFuelUsed'] : $jobCard?->approximate_fuel_used,
            'driver_details' => array_key_exists('driverDetails', $validated) ? $validated['driverDetails'] : $jobCard?->driver_details,
            'driver_allowance' => array_key_exists('driverAllowance', $validated) ? $validated['driverAllowance'] : $jobCard?->driver_allowance,
            'driver_allowances' => array_key_exists('driverAllowances', $validated) ? $validated['driverAllowances'] : $jobCard?->driver_allowances,
            'route_itinerary' => $jobCard?->route_itinerary,
            'guide_language' => $jobCard?->guide_language,
        ];

        $manualRouteItinerary = $this->normalizeRouteItinerary($validated['routeItinerary'] ?? null);

        if ($this->isSafariType($type)) {
            $payload['route_itinerary'] = ! empty($manualRouteItinerary)
                ? $manualRouteItinerary
                : $this->resolveSafariItinerary(
                    $payload['lead_id'] ? (int) $payload['lead_id'] : null,
                    $jobCard
                );

            $payload['lease_contract_id'] = null;
            $payload['lease_allocation_id'] = null;

            $payload['reason'] = null;
            $payload['client_details'] = null;
            $payload['location'] = null;
            $payload['kms'] = null;
            $payload['odometer_out'] = null;
            $payload['odometer_in'] = null;
            $payload['mileage'] = null;
            $payload['fuel_gauge_out'] = null;
            $payload['fuel_gauge_in'] = null;
            $payload['approximate_fuel_used'] = null;
            $payload['driver_details'] = null;
        } elseif ($this->isLeaseType($type)) {
            $payload['lead_id'] = null;
            $payload['route_itinerary'] = ! empty($manualRouteItinerary)
                ? $manualRouteItinerary
                : $this->normalizeRouteItinerary($jobCard?->route_itinerary);

            $allocation = $payload['lease_allocation_id']
                ? LeaseAllocation::query()->with(['vehicle', 'driver', 'leaseContract'])->find($payload['lease_allocation_id'])
                : null;

            if ($allocation) {
                // Allocation is the source of truth for vehicle, dates, itinerary.
                $payload['lease_contract_id'] = $allocation->lease_contract_id;
                $payload['vehicle_id'] = $allocation->vehicle_id;

                if ($allocation->start_date) {
                    $payload['safari_start_date'] = \Carbon\Carbon::parse($allocation->start_date)->toDateString();
                }
                if ($allocation->end_date) {
                    $payload['safari_end_date'] = \Carbon\Carbon::parse($allocation->end_date)->toDateString();
                }
                if ($allocation->start_date && $allocation->end_date) {
                    $payload['number_of_days'] = \Carbon\Carbon::parse($allocation->start_date)
                        ->diffInDays(\Carbon\Carbon::parse($allocation->end_date)) + 1;
                }

                $itineraryText = trim((string) ($allocation->itinerary ?? ''));
                if ($itineraryText !== '') {
                    // Keep route_summary short enough for DB column constraints.
                    if (empty($payload['route_summary'])) {
                        $payload['route_summary'] = Str::limit($itineraryText, 240, '...');
                    }

                    if (empty($payload['additional_details'])) {
                        $payload['additional_details'] = $itineraryText;
                    }
                }

                if (empty($payload['route_itinerary'])) {
                    $payload['route_itinerary'] = $this->normalizeRouteItinerary($allocation->itinerary_items);
                }

                $contract = $allocation->leaseContract;
                if ($contract) {
                    $payload['tour_operator_client_name'] = $payload['tour_operator_client_name'] ?: $contract->client_name;
                }

                if ($allocation->driver) {
                    $payload['driver_details'] = $allocation->driver->name;
                }
            } else {
                $payload['lease_contract_id'] = null;
            }

            $payload['reason'] = null;
            $payload['client_details'] = null;
            $payload['location'] = null;
            $payload['kms'] = null;
            $payload['booking_reference_no'] = null;
            $payload['contact_person'] = null;
            $payload['contact_number'] = null;
            $payload['contact_email'] = null;
            $payload['adults'] = null;
            $payload['children'] = null;
            $payload['nationality'] = null;
            $payload['pickup_location'] = null;
            $payload['dropoff_location'] = null;
        } else {
            $payload['lead_id'] = null;
            $payload['lease_contract_id'] = null;
            $payload['lease_allocation_id'] = null;

            if (! in_array($type, self::REASON_TYPES, true)) {
                $payload['reason'] = null;
            }

            if ($type !== 'Client Viewing') {
                $payload['client_details'] = null;
                $payload['location'] = null;
                $payload['kms'] = null;
            }

            if (! in_array($type, self::VEHICLE_RUN_TYPES, true)) {
                $payload['odometer_out'] = null;
                $payload['odometer_in'] = null;
                $payload['mileage'] = null;
                $payload['fuel_gauge_out'] = null;
                $payload['fuel_gauge_in'] = null;
                $payload['approximate_fuel_used'] = null;
                $payload['driver_details'] = null;
            }

            $payload['number_of_days'] = null;
            $payload['pickup_location'] = null;
            $payload['dropoff_location'] = null;
            $payload['booking_reference_no'] = null;
            $payload['tour_operator_client_name'] = null;
            $payload['contact_person'] = null;
            $payload['contact_number'] = null;
            $payload['contact_email'] = null;
            $payload['adults'] = null;
            $payload['children'] = null;
            $payload['nationality'] = null;
        }

        return $payload;
    }

    private function isSafariType(?string $type): bool
    {
        return is_string($type) && str_starts_with(strtolower($type), 'safari');
    }

    private function isLeaseType(?string $type): bool
    {
        return is_string($type) && strtolower(trim($type)) === 'long term lease';
    }

    private function isStatusManagedJobCardType(?string $type): bool
    {
        return $this->isSafariType($type) || $this->isLeaseType($type);
    }

    private function findDuplicateLeaseAllocationJobCard(int $leaseAllocationId, ?int $ignoreJobCardId = null): ?JobCard
    {
        $query = JobCard::query()
            ->where('lease_allocation_id', $leaseAllocationId)
            ->whereRaw('LOWER(TRIM(type)) = ?', ['long term lease']);

        if ($ignoreJobCardId !== null) {
            $query->where('id', '!=', $ignoreJobCardId);
        }

        return $query->first();
    }

    private function findDuplicateSafariLeadJobCard(int $leadId, ?int $ignoreJobCardId = null): ?JobCard
    {
        $query = JobCard::query()
            ->where('lead_id', $leadId)
            ->whereRaw('LOWER(TRIM(type)) LIKE ?', ['safari%']);

        if ($ignoreJobCardId !== null) {
            $query->where('id', '!=', $ignoreJobCardId);
        }

        return $query->orderBy('id')->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(JobCard $jobCard): array
    {
        $timeOut = $jobCard->time_out ? date('H:i', strtotime((string) $jobCard->time_out)) : null;
        $timeIn = $jobCard->time_in ? date('H:i', strtotime((string) $jobCard->time_in)) : null;
        $routeItinerary = $this->normalizeRouteItinerary($jobCard->route_itinerary);

        if (empty($routeItinerary) && $this->isSafariType($jobCard->type)) {
            $routeItinerary = $this->resolveSafariItinerary($jobCard->lead_id ? (int) $jobCard->lead_id : null, $jobCard);
        }

        return [
            'id' => $jobCard->id,
            'jobCardNo' => $jobCard->job_card_no,
            'groupName' => $this->resolveGroupName($jobCard),
            'leadId' => $jobCard->lead_id,
            'vehicleId' => $jobCard->vehicle_id,
            'leaseContractId' => $jobCard->lease_contract_id,
            'leaseAllocationId' => $jobCard->lease_allocation_id,
            'type' => $jobCard->type,
            'status' => $jobCard->status,
            'safariStartDate' => optional($jobCard->safari_start_date)->format('Y-m-d'),
            'safariEndDate' => optional($jobCard->safari_end_date)->format('Y-m-d'),
            'timeOut' => $timeOut,
            'timeIn' => $timeIn,
            'routeSummary' => $jobCard->route_summary,
            'routePoints' => $jobCard->route_points ?? [],
            'routeDistanceKm' => $jobCard->route_distance_km !== null ? (float) $jobCard->route_distance_km : null,
            'additionalDistanceKm' => $jobCard->additional_distance_km !== null ? (float) $jobCard->additional_distance_km : null,
            'additionalDistanceNote' => $jobCard->additional_distance_note,
            'additionalDetails' => $jobCard->additional_details,
            'numberOfDays' => $jobCard->number_of_days,
            'pickupLocation' => $jobCard->pickup_location,
            'dropoffLocation' => $jobCard->dropoff_location,
            'bookingReferenceNo' => $jobCard->booking_reference_no,
            'tourOperatorClientName' => $jobCard->tour_operator_client_name,
            'contactPerson' => $jobCard->contact_person,
            'contactNumber' => $jobCard->contact_number,
            'contactEmail' => $jobCard->contact_email,
            'adults' => $jobCard->adults,
            'children' => $jobCard->children,
            'nationality' => $jobCard->nationality,
            'reason' => $jobCard->reason,
            'clientDetails' => $jobCard->client_details,
            'location' => $jobCard->location,
            'kms' => $jobCard->kms !== null ? (float) $jobCard->kms : null,
            'odometerOut' => $jobCard->odometer_out,
            'odometerIn' => $jobCard->odometer_in,
            'mileage' => $jobCard->mileage,
            'fuelGaugeOut' => $jobCard->fuel_gauge_out !== null ? (float) $jobCard->fuel_gauge_out : null,
            'fuelGaugeIn' => $jobCard->fuel_gauge_in !== null ? (float) $jobCard->fuel_gauge_in : null,
            'approximateFuelUsed' => $jobCard->approximate_fuel_used !== null ? (float) $jobCard->approximate_fuel_used : null,
            'driverDetails' => $jobCard->driver_details,
            'driverAllowance' => $jobCard->driver_allowance !== null ? (float) $jobCard->driver_allowance : null,
            'driverAllowances' => is_array($jobCard->driver_allowances) ? $jobCard->driver_allowances : [],
            'perDiemCount' => (int) ($jobCard->per_diem_vouchers_count ?? 0),
            'paidPerDiemCount' => (int) ($jobCard->paid_per_diem_vouchers_count ?? 0),
            'assignedDriverCount' => (int) ($jobCard->assigned_driver_count ?? 0),
            'perDiemUpdatedAt' => $jobCard->per_diem_vouchers_max_updated_at
                ? \Carbon\Carbon::parse($jobCard->per_diem_vouchers_max_updated_at)->toISOString()
                : null,
            'vehicle' => $jobCard->vehicle ? [
                'id' => $jobCard->vehicle->id,
                'vehicle_no' => $jobCard->vehicle->vehicle_no,
                'plate_no' => $jobCard->vehicle->plate_no,
            ] : null,
            'leaseContract' => $jobCard->leaseContract ? [
                'id' => $jobCard->leaseContract->id,
                'clientName' => $jobCard->leaseContract->client_name,
                'leaseType' => $jobCard->leaseContract->lease_type,
                'startDate' => optional($jobCard->leaseContract->start_date)->toDateString(),
                'endDate' => optional($jobCard->leaseContract->end_date)->toDateString(),
                'status' => $jobCard->leaseContract->status,
            ] : null,
            'leaseAllocation' => $jobCard->leaseAllocation ? [
                'id' => $jobCard->leaseAllocation->id,
                'leaseContractId' => $jobCard->leaseAllocation->lease_contract_id,
                'groupName' => $jobCard->leaseAllocation->group_name,
                'vehicleId' => $jobCard->leaseAllocation->vehicle_id,
                'startDate' => optional($jobCard->leaseAllocation->start_date)->toDateString(),
                'endDate' => optional($jobCard->leaseAllocation->end_date)->toDateString(),
                'itinerary' => $jobCard->leaseAllocation->itinerary,
                'status' => $jobCard->leaseAllocation->status,
                'vehicle' => $jobCard->leaseAllocation->vehicle ? [
                    'id' => $jobCard->leaseAllocation->vehicle->id,
                    'vehicleNo' => $jobCard->leaseAllocation->vehicle->vehicle_no,
                    'plateNo' => $jobCard->leaseAllocation->vehicle->plate_no,
                ] : null,
                'driver' => $jobCard->leaseAllocation->driver ? [
                    'id' => $jobCard->leaseAllocation->driver->id,
                    'name' => $jobCard->leaseAllocation->driver->name,
                ] : null,
                'contract' => $jobCard->leaseAllocation->leaseContract ? [
                    'id' => $jobCard->leaseAllocation->leaseContract->id,
                    'clientName' => $jobCard->leaseAllocation->leaseContract->client_name,
                    'groupName' => $jobCard->leaseAllocation->leaseContract->group_name,
                    'leaseType' => $jobCard->leaseAllocation->leaseContract->lease_type,
                ] : null,
            ] : null,
            // Backward-compatible fields used by existing PDF/template consumers
            'numberOfClients' => [
                'adults' => $jobCard->adults,
                'children' => $jobCard->children,
            ],
            'guideLanguage' => $jobCard->guide_language,
            'routeItinerary' => $routeItinerary,
            'createdAt' => $jobCard->created_at?->toISOString(),
            'updatedAt' => $jobCard->updated_at?->toISOString(),
        ];
    }

    private function resolveGroupName(JobCard $jobCard): ?string
    {
        if ($this->isLeaseType($jobCard->type)) {
            $leaseGroupName = $jobCard->leaseAllocation?->group_name
                ?? $jobCard->leaseAllocation?->leaseContract?->group_name
                ?? $jobCard->leaseContract?->group_name;

            return $leaseGroupName !== null && trim((string) $leaseGroupName) !== ''
                ? (string) $leaseGroupName
                : null;
        }

        $leadGroupName = $jobCard->lead?->group_name;
        if ($leadGroupName !== null && trim((string) $leadGroupName) !== '') {
            return (string) $leadGroupName;
        }

        if ($jobCard->lead_id) {
            $latestQuotation = \App\Models\Quotation::query()
                ->where('lead_id', $jobCard->lead_id)
                ->latest('quote_date')
                ->latest('id')
                ->first();

            $quotationGroupName = $latestQuotation?->group_name;
            if ($quotationGroupName !== null && trim((string) $quotationGroupName) !== '') {
                return (string) $quotationGroupName;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function resolveSafariItinerary(?int $leadId, ?JobCard $jobCard): array
    {
        $existing = $this->normalizeRouteItinerary($jobCard?->route_itinerary);
        if (!empty($existing)) {
            return $existing;
        }

        if (!$leadId) {
            return [];
        }

        $lead = Lead::query()
            ->with([
                'quotations' => fn($query) => $query->latest('quote_date')->latest('id')->limit(1),
            ])
            ->find($leadId);

        $quotation = $lead?->quotations->first();
        $sections = is_array($quotation?->day_sections) ? $quotation->day_sections : [];

        return $this->normalizeRouteItinerary($sections);
    }

    /**
     * @param mixed $itinerary
     * @return array<int, array<string, string>>
     */
    private function normalizeRouteItinerary(mixed $itinerary): array
    {
        if (!is_array($itinerary)) {
            return [];
        }

        return collect($itinerary)
            ->map(function ($item): ?array {
                if (is_string($item)) {
                    $value = trim($item);
                    if ($value === '') {
                        return null;
                    }

                    return [
                        'date' => $value,
                        'dayDescription' => '',
                        'allowancePerDay' => null,
                    ];
                }

                if (!is_array($item)) {
                    return null;
                }

                $date = trim((string) ($item['date'] ?? $item['dayDate'] ?? $item['dayTitle'] ?? ''));
                $description = trim((string) ($item['dayDescription'] ?? $item['dateDescription'] ?? $item['description'] ?? $item['details'] ?? ''));
                $allowanceRaw = $item['allowancePerDay'] ?? $item['allowance_per_day'] ?? null;
                $allowance = null;
                if ($allowanceRaw !== null && $allowanceRaw !== '') {
                    if (is_numeric($allowanceRaw)) {
                        $allowance = (float) $allowanceRaw;
                    }
                }

                if ($date === '' && $description === '' && $allowance === null) {
                    return null;
                }

                return [
                    'date' => $date,
                    'dayDescription' => $description,
                    'allowancePerDay' => $allowance,
                ];
            })
            ->filter()
            ->values()
            ->all();
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
