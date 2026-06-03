<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Job Card {{ $jobCard['jobCardNo'] ?? '#' . $jobCard['id'] }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        :root {
            --brand-gold: #c9a236;
            --brand-teal: #32595a;
            --brand-red: #e5252a;
            --ink: #1f2a2a;
            --muted: #5d6b6b;
            --line: #d6d6d6;
            --panel: #f8f8f8;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: var(--ink);
            font-size: 10.5px;
            background: #ececec;
        }

        * {
            box-sizing: border-box;
        }

        .sheet {
            width: 100%;
            min-height: 1010px;
            background: #ffffff;
            border: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }

        .top-band {
            height: 10px;
            background: var(--brand-gold);
            border-bottom: 2px solid var(--brand-red);
        }

        .watermark {
            position: absolute;
            width: 320px;
            height: 320px;
            right: -100px;
            top: 130px;
            border: 20px solid rgba(50, 89, 90, 0.04);
            border-radius: 50%;
            pointer-events: none;
        }

        .content {
            padding: 14px;
            position: relative;
            z-index: 2;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 120px;
            max-height: 120px;
            object-fit: contain;
            margin-top: -6px;
        }

        .brand-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--brand-teal);
            letter-spacing: 0.6px;
            line-height: 1;
        }

        .brand-tagline {
            margin-top: 6px;
            font-size: 11px;
            color: var(--brand-red);
            font-style: italic;
            font-weight: 700;
        }

        .job-heading {
            margin-top: 10px;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            background: var(--brand-teal);
            padding: 6px 10px;
            border-left: 4px solid var(--brand-gold);
            letter-spacing: 0.4px;
        }

        .contact-line {
            margin-top: 8px;
            font-size: 9px;
            color: var(--muted);
            white-space: nowrap;
        }

        .section-title {
            margin-top: 12px;
            margin-bottom: 6px;
            padding: 6px 9px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-left: 4px solid var(--brand-gold);
            font-size: 10.5px;
            font-weight: 700;
            color: var(--brand-teal);
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .grid td {
            border: 1px solid var(--line);
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10px;
        }

        .label {
            font-weight: 700;
            color: #2d4243;
        }

        .itinerary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .itinerary th,
        .itinerary td {
            border: 1px solid var(--line);
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: top;
        }

        .itinerary th {
            background: #f4efe3;
            color: var(--brand-teal);
            text-align: left;
            font-weight: 700;
        }

        .notes {
            border: 1px solid var(--line);
            background: #fcfcfc;
            min-height: 78px;
            padding: 8px;
            white-space: pre-wrap;
            font-size: 10px;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        .signatures td {
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
            font-size: 10px;
        }

        .sign-label {
            font-weight: 700;
            color: var(--brand-red);
            margin-bottom: 2px;
        }

        .sign-line {
            margin-top: 32px;
            border-top: 1px solid #1f2937;
            width: 86%;
            padding-top: 4px;
            font-size: 9.5px;
            color: #374151;
        }

        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px dashed var(--line);
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        $type = $jobCard['type'] ?? 'Safari';
        $isSafari = is_string($type) && str_starts_with(strtolower($type), 'safari');
        $isLease = is_string($type) && str_contains(strtolower($type), 'lease');
        $formatDisplayDate = function ($value) {
            if (empty($value)) {
                return '-';
            }

            $raw = trim((string) $value);

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
                return $raw;
            }

            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $matches)) {
                return $matches[3] . '/' . $matches[2] . '/' . $matches[1];
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable $exception) {
                return $raw;
            }
        };

        $clientDetails = $jobCard['tourOperatorClientName'] ?? ($jobCard['clientDetails'] ?? '-');

        $driverDetails = trim((string) ($jobCard['driverDetails'] ?? ''));

        $normalizeVehicleToken = function ($value) {
            return strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $value));
        };

        $formatVehicleLabel = function ($vehicleNo, $plateNo) use ($normalizeVehicleToken) {
            $vehicleNo = trim((string) $vehicleNo);
            $plateNo = trim((string) $plateNo);

            if ($vehicleNo === '' && $plateNo === '') {
                return '-';
            }

            if ($vehicleNo === '') {
                return $plateNo;
            }

            if ($plateNo === '') {
                return $vehicleNo;
            }

            if ($normalizeVehicleToken($vehicleNo) === $normalizeVehicleToken($plateNo)) {
                return $vehicleNo;
            }

            return $vehicleNo . ' / ' . $plateNo;
        };

        $allocatedVehicles = is_array($jobCard['allocatedVehicles'] ?? null) ? $jobCard['allocatedVehicles'] : [];
    @endphp

    <div class="sheet">
        <div class="top-band"></div>
        <div class="watermark"></div>

        <div class="content">
            <table class="header">
                <tr>
                    <td style="width:70%;">
                        <div class="brand-title">{{ strtoupper($company['name'] ?? config('app.name')) }}</div>
                        <div class="brand-tagline">Conquer the wild</div>
                        <div class="job-heading">JOB CARD</div>
                        <div class="contact-line">
                            523 Engutoto, Dharam Singh Road, Njiro Industrial Area, P.O Box 613, Arusha, Tanzania | +255
                            683 555 666 | info@sher.co.tz
                        </div>
                    </td>
                    <td style="width:30%;text-align:right;">
                        @if (!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="section-title">1. Booking / Job Information</div>
            <table class="grid">
                <tr>
                    <td><span class="label">Job Card No:</span> {{ $jobCard['jobCardNo'] }}</td>
                    <td><span class="label">Client Details:</span> {{ $clientDetails }}</td>
                </tr>
                <tr>
                    <td><span class="label">Status:</span> {{ $jobCard['status'] ?? 'Open' }}</td>
                    <td><span class="label">Booking Number:</span> {{ $jobCard['bookingReferenceNo'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><span class="label">Group Name:</span> {{ $jobCard['groupName'] ?? '-' }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td><span class="label">Start Date:</span>
                        {{ $formatDisplayDate($jobCard['safariStartDate'] ?? null) }}</td>
                    <td><span class="label">End Date:</span>
                        {{ $formatDisplayDate($jobCard['safariEndDate'] ?? null) }}</td>
                </tr>
                <tr>
                    <td><span class="label">Pax Adults:</span> {{ $jobCard['adults'] ?? '-' }}</td>
                    <td><span class="label">Pax Children:</span> {{ $jobCard['children'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Vehicle(s):</span>
                        @if (!empty($allocatedVehicles))
                            @foreach ($allocatedVehicles as $allocationVehicle)
                                <div>
                                    {{ $formatVehicleLabel($allocationVehicle['vehicleNo'] ?? '', $allocationVehicle['plateNo'] ?? '') }}
                                    @if (!empty($allocationVehicle['driverName']))
                                        - Driver: {{ $allocationVehicle['driverName'] }}
                                    @endif
                                </div>
                            @endforeach
                        @elseif (!empty($jobCard['vehicle']))
                            {{ $formatVehicleLabel($jobCard['vehicle']['vehicle_no'] ?? '', $jobCard['vehicle']['plate_no'] ?? '') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @if ($driverDetails !== '')
                    <tr>
                        <td colspan="2"><span class="label">Driver Details:</span> {{ $driverDetails }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="2"><span class="label">Route / Summary:</span>
                        {{ $jobCard['routeSummary'] ?? '-' }}</td>
                </tr>
            </table>

            @if ($isSafari || $isLease)
                @php
                    $itineraryDays = $jobCard['routeItinerary'] ?? [];
                    $driverAllowanceValue = $jobCard['driverAllowance'] ?? null;
                @endphp
                <div class="section-title">2. Itinerary</div>
                <table class="itinerary">
                    <thead>
                        <tr>
                            <th style="width:25%;">Date</th>
                            <th>Date Description</th>
                            <th style="width:20%;">Allowance/Day</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itineraryDays as $day)
                            <tr>
                                <td>
                                    {{ $formatDisplayDate($day['date'] ?? ($day['dayDate'] ?? ($day['dayTitle'] ?? null))) }}
                                </td>
                                <td>{{ $day['dayDescription'] ?? ($day['dateDescription'] ?? ($day['description'] ?? '-')) }}
                                </td>
                                <td>
                                    @if (isset($day['allowancePerDay']) && $day['allowancePerDay'] !== null && $day['allowancePerDay'] !== '')
                                        {{ number_format((float) $day['allowancePerDay'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">No itinerary details provided.</td>
                            </tr>
                        @endforelse
                        <tr>
                            <td colspan="2" style="text-align:right;"><span class="label">Driver Allowance
                                    Total:</span></td>
                            <td>
                                @if ($driverAllowanceValue !== null && $driverAllowanceValue !== '')
                                    {{ number_format((float) $driverAllowanceValue, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div class="section-title">2. Job Type Details</div>
                <table class="grid">
                    <tr>
                        <td><span class="label">Reason:</span> {{ $jobCard['reason'] ?? '-' }}</td>
                        <td><span class="label">Client Details:</span> {{ $jobCard['clientDetails'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><span class="label">Location:</span> {{ $jobCard['location'] ?? '-' }}</td>
                        <td><span class="label">KMS:</span> {{ $jobCard['kms'] ?? '-' }}</td>
                    </tr>
                </table>

                <div class="section-title">3. Vehicle Run Details</div>
                <table class="grid">
                    <tr>
                        <td><span class="label">Odometer Out:</span> {{ $jobCard['odometerOut'] ?? '-' }}</td>
                        <td><span class="label">Odometer In:</span> {{ $jobCard['odometerIn'] ?? '-' }}</td>
                        <td><span class="label">Mileage:</span> {{ $jobCard['mileage'] ?? '-' }}</td>
                    </tr>
                    @if ($driverDetails !== '')
                        <tr>
                            <td colspan="3"><span class="label">Driver Details:</span>
                                {{ $driverDetails }}</td>
                        </tr>
                    @endif
                </table>
            @endif

            <div class="section-title">Additional Details</div>
            <div class="notes">{{ $jobCard['additionalDetails'] ?? '-' }}</div>

            <table class="signatures">
                <tr>
                    <td>
                        <div class="sign-label">Prepared By</div>
                        <div class="sign-line">Name & Signature</div>
                        <div style="margin-top: 10px;">Date: ____________________</div>
                    </td>
                    <td>
                        <div class="sign-label">Fleet Manager</div>
                        <div class="sign-line">Fleet Manager Signature</div>
                        <div style="margin-top: 10px;">Date: ____________________</div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                Generated on {{ now()->format('Y-m-d H:i') }}
            </div>
        </div>
    </div>
</body>

</html>
