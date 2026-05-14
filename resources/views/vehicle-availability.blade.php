<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vehicle Availability {{ $monthLabel }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2a2a;
            font-size: 10.5px;
            background: #ececec;
        }

        * {
            box-sizing: border-box;
        }

        .sheet {
            width: 100%;
            min-height: 740px;
            background: #ffffff;
            border: 1px solid #d6d6d6;
            position: relative;
            overflow: hidden;
        }

        .top-band {
            height: 10px;
            background: #c9a236;
            border-bottom: 2px solid #e5252a;
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
            padding: 16px 18px;
            position: relative;
            z-index: 2;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 110px;
            max-height: 110px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: #32595a;
            letter-spacing: 0.6px;
            line-height: 1;
        }

        .brand-tagline {
            margin-top: 5px;
            font-size: 10.5px;
            color: #e5252a;
            font-style: italic;
            font-weight: 700;
        }

        .doc-heading {
            margin-top: 10px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            color: #ffffff;
            background: #32595a;
            padding: 6px 12px;
            border-left: 4px solid #c9a236;
            letter-spacing: 0.5px;
        }

        .contact-line {
            margin-top: 7px;
            font-size: 9.5px;
            color: #5d6b6b;
        }

        .section-title {
            margin: 12px 0 8px;
            padding: 6px 9px;
            background: #f8f8f8;
            border: 1px solid #d6d6d6;
            border-left: 4px solid #c9a236;
            font-size: 10.5px;
            font-weight: 700;
            color: #32595a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #c0c0c0;
            padding: 5px 6px;
            vertical-align: top;
            word-break: break-word;
            font-size: 9.5px;
        }

        th {
            background: #f4efe3;
            color: #32595a;
            font-weight: 700;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .small {
            font-size: 9px;
            color: #374151;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .summary td {
            border: 1px solid #d6d6d6;
            padding: 8px 10px;
            background: #f8f8f8;
            font-size: 10px;
        }

        .summary .label {
            font-weight: 700;
            color: #32595a;
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
                        <div class="doc-heading">VEHICLE AVAILABILITY REPORT</div>
                        <div class="contact-line">
                            {{ $company['address'] ?? '' }}
                            {{ !empty($company['phone']) ? ' | ' . $company['phone'] : '' }}
                            {{ !empty($company['email']) ? ' | ' . $company['email'] : '' }}
                            {{ !empty($company['tax_registration_number']) ? ' | TIN: ' . $company['tax_registration_number'] : '' }}
                        </div>
                    </td>
                    <td style="width:30%;text-align:right;">
                        @if (!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        @endif
                    </td>
                </tr>
            </table>

            <table class="summary">
                <tr>
                    <td><span class="label">Month:</span> {{ $monthLabel }}</td>
                    <td><span class="label">Generated At:</span> {{ $generatedAt }}</td>
                    <td><span class="label">Vehicles:</span> {{ count($rows) }}</td>
                </tr>
            </table>

            <div class="section-title">Vehicle Availability Summary</div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Vehicle No</th>
                        <th style="width: 8%;">Plate No</th>
                        <th style="width: 8%;">Make</th>
                        <th style="width: 9%;">Model</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 7%;" class="right">Booked Days</th>
                        <th style="width: 7%;" class="right">Total Days</th>
                        <th style="width: 9%;" class="right">Availability %</th>
                        <th style="width: 34%;">Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['vehicleNo'] }}</td>
                            <td>{{ $row['plateNo'] }}</td>
                            <td>{{ $row['make'] }}</td>
                            <td>{{ $row['model'] }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td class="right">{{ $row['bookedDays'] }}</td>
                            <td class="right">{{ $row['totalDays'] }}</td>
                            <td class="right">{{ $row['availabilityPercent'] }}</td>
                            <td class="small">{{ $row['bookingsSummary'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
