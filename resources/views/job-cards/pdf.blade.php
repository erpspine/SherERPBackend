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
            font-size: 9.5px;
            color: var(--muted);
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
                            {{ $company['address'] ?? '' }}
                            {{ !empty($company['phone']) ? ' | ' . $company['phone'] : '' }}
                            {{ !empty($company['email']) ? ' | ' . $company['email'] : '' }}
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
                    <td><span class="label">Booking Ref No:</span> {{ $jobCard['bookingReferenceNo'] }}</td>
                </tr>
                <tr>
                    <td><span class="label">Tour Operator / Client:</span> {{ $jobCard['tourOperatorClientName'] }}</td>
                    <td><span class="label">Contact Person:</span> {{ $jobCard['contactPerson'] }}</td>
                </tr>
                <tr>
                    <td><span class="label">Contact Number:</span> {{ $jobCard['contactNumber'] }}</td>
                    <td><span class="label">Email:</span> {{ $jobCard['contactEmail'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Adults:</span> {{ $jobCard['numberOfClients']['adults'] }}
                        &nbsp;|&nbsp;
                        <span class="label">Children:</span> {{ $jobCard['numberOfClients']['children'] }}
                    </td>
                    <td><span class="label">Nationality:</span> {{ $jobCard['nationality'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2"><span class="label">Guide Language:</span> {{ $jobCard['guideLanguage'] }}</td>
                </tr>
            </table>

            <div class="section-title">2. Safari Details</div>
            <table class="grid">
                <tr>
                    <td><span class="label">Start Date:</span> {{ $jobCard['safariStartDate'] }}</td>
                    <td><span class="label">End Date:</span> {{ $jobCard['safariEndDate'] }}</td>
                    <td><span class="label">Number of Days:</span> {{ $jobCard['numberOfDays'] }}</td>
                </tr>
                <tr>
                    <td colspan="3"><span class="label">Route / Summary:</span> {{ $jobCard['routeSummary'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><span class="label">Pick-up Location:</span> {{ $jobCard['pickupLocation'] ?? '-' }}</td>
                    <td colspan="2"><span class="label">Drop-off Location:</span> {{ $jobCard['dropoffLocation'] ?? '-' }}</td>
                </tr>
            </table>

            <div class="section-title">Itinerary</div>
            <table class="itinerary">
                <thead>
                    <tr>
                        <th style="width:30%;">Day</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($jobCard['routeItinerary'] ?? []) as $day)
                        <tr>
                            <td>{{ $day['dayTitle'] ?? '-' }}</td>
                            <td>{{ $day['description'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No itinerary details provided.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

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
