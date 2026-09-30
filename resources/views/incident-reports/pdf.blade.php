<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Performance Dashboard Report</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2a2a;
            font-size: 9px;
            background: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        .sheet {
            width: 100%;
            background: #ffffff;
            border: 1px solid #d6d6d6;
            position: relative;
            overflow: visible;
        }

        .top-band {
            height: 10px;
            background: #c9a236;
            border-bottom: 2px solid #e5252a;
        }

        .content {
            padding: 14px 16px;
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
            width: 100px;
            max-height: 90px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 21px;
            font-weight: 800;
            color: #32595a;
            letter-spacing: 0.5px;
            line-height: 1;
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
            font-size: 8.8px;
            color: #5d6b6b;
            white-space: nowrap;
        }

        .generated {
            text-align: right;
            color: #5d6b6b;
            font-size: 9px;
            line-height: 1.6;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .summary td {
            border: 1px solid #d6d6d6;
            background: #f8f8f8;
            padding: 7px 9px;
            font-size: 9px;
            vertical-align: top;
        }

        .summary .label {
            color: #32595a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }

        .summary .value {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 800;
            color: #1f2a2a;
        }

        .section-title {
            margin: 10px 0 6px;
            padding: 6px 9px;
            background: #f8f8f8;
            border: 1px solid #d6d6d6;
            border-left: 4px solid #c9a236;
            font-size: 10px;
            font-weight: 700;
            color: #32595a;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tr {
            page-break-inside: avoid;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #c0c0c0;
            padding: 4px 5px;
            vertical-align: top;
            line-height: 1.25;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .report-table th {
            background: #f4efe3;
            color: #32595a;
            font-weight: 700;
            text-align: left;
            font-size: 8.5px;
        }

        .muted {
            color: #5d6b6b;
            font-size: 8px;
        }

        .status-open {
            color: #92400e;
            font-weight: 700;
        }

        .status-closed {
            color: #047857;
            font-weight: 700;
        }

        .photo-strip img {
            width: 42px;
            height: 32px;
            border: 1px solid #d6d6d6;
            margin: 0 2px 2px 0;
        }

        .text-cell {
            font-size: 8px;
            line-height: 1.3;
        }
    </style>
</head>

<body>
    <div class="sheet">
        <div class="top-band"></div>
        <div class="content">
            <table class="header">
                <tr>
                    <td style="width: 120px;">
                        @if ($logoDataUri)
                            <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
                        @endif
                    </td>
                    <td>
                        <div class="brand-title">{{ $company['name'] ?? config('app.name') }}</div>
                        <div class="doc-heading">PERFORMANCE DASHBOARD REPORT</div>
                        <div class="contact-line">
                            {{ $company['address'] ?? '' }}
                            @if (!empty($company['phone']))
                                | Tel: {{ $company['phone'] }}
                            @endif
                            @if (!empty($company['email']))
                                | Email: {{ $company['email'] }}
                            @endif
                        </div>
                    </td>
                    <td class="generated" style="width: 210px;">
                        Generated: {{ $generatedAt }}<br>
                        Period:
                        {{ $filters['dateFrom'] ?: 'Start' }} to {{ $filters['dateTo'] ?: 'End' }}<br>
                        Status: {{ $filters['status'] ?: 'All' }}<br>
                        Type: {{ $filters['reportType'] ?: 'All' }}
                    </td>
                </tr>
            </table>

            <table class="summary">
                <tr>
                    <td>
                        <div class="label">Total Records</div>
                        <div class="value">{{ $stats['total'] }}</div>
                    </td>
                    <td>
                        <div class="label">Open</div>
                        <div class="value">{{ $stats['open'] }}</div>
                    </td>
                    <td>
                        <div class="label">Closed</div>
                        <div class="value">{{ $stats['closed'] }}</div>
                    </td>
                    <td>
                        <div class="label">Accidents</div>
                        <div class="value">{{ $stats['accidents'] }}</div>
                    </td>
                    <td>
                        <div class="label">Search</div>
                        <div class="value" style="font-size: 9px;">{{ $filters['search'] ?: 'All' }}</div>
                    </td>
                </tr>
            </table>

            <div class="section-title">Performance Records</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 7%;">Date</th>
                        <th style="width: 8%;">Vehicle</th>
                        <th style="width: 10%;">Driver</th>
                        <th style="width: 13%;">Booking</th>
                        <th style="width: 7%;">Type</th>
                        <th style="width: 29%;">Description</th>
                        <th style="width: 5%;">Status</th>
                        <th style="width: 13%;">Closing Remarks / Action</th>
                        <th style="width: 8%;">Photos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $vehicle = is_array($row['vehicle'] ?? null) ? $row['vehicle'] : [];
                            $driver = is_array($row['driver'] ?? null) ? $row['driver'] : [];
                            $safari = is_array($row['safari'] ?? null) ? $row['safari'] : null;
                            $lease = is_array($row['leaseAllocation'] ?? null) ? $row['leaseAllocation'] : null;
                            $leaseContract = is_array($lease['contract'] ?? null) ? $lease['contract'] : [];
                            $vehicleNo = (string) ($vehicle['vehicleNo'] ?? '');
                            $plateNo = (string) ($vehicle['plateNo'] ?? '');
                            $showPlate = $plateNo !== '' && strcasecmp($vehicleNo, $plateNo) !== 0;
                            $photos = is_array($row['photos'] ?? null) ? $row['photos'] : [];
                            $displayDate = !empty($row['date'])
                                ? \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y')
                                : '';
                            $description = (string) ($row['description'] ?? '');
                            $actionTaken = (string) ($row['actionTaken'] ?? '');
                            $closingRemarks = (string) ($row['closingRemarks'] ?? '');
                            $booking = $lease
                                ? trim(
                                    implode(
                                        ' - ',
                                        array_filter([
                                            'Lease #' . ($lease['id'] ?? ''),
                                            $lease['groupName'] ?? null,
                                            $leaseContract['leaseType'] ?? null,
                                        ]),
                                    ),
                                )
                                : trim(
                                    implode(
                                        ' - ',
                                        array_filter([$safari['bookingRef'] ?? null, $safari['groupName'] ?? null]),
                                    ),
                                );
                            $client = $lease ? $leaseContract['clientName'] ?? '' : $safari['clientCompany'] ?? '';
                        @endphp
                        <tr>
                            <td>{{ $displayDate }}</td>
                            <td>
                                {{ $vehicleNo ?: '-' }}
                                @if ($showPlate)
                                    <br><span class="muted">{{ $plateNo }}</span>
                                @endif
                            </td>
                            <td>{{ $driver['name'] ?? '' }}</td>
                            <td class="text-cell">
                                {{ $booking ?: '-' }}<br>
                                <span class="muted">{{ $client }}</span>
                            </td>
                            <td>{{ $row['reportType'] ?? '' }}</td>
                            <td class="text-cell">
                                {{ $description }}
                            </td>
                            <td class="{{ ($row['status'] ?? '') === 'Closed' ? 'status-closed' : 'status-open' }}">
                                {{ $row['status'] ?? '' }}
                            </td>
                            <td class="text-cell">
                                {{ $closingRemarks }}
                                @if ($actionTaken !== '')
                                    <br><span class="muted">Action: {{ $actionTaken }}</span>
                                @endif
                            </td>
                            <td>
                                @if (count($photos) > 0)
                                    <div class="photo-strip">
                                        @foreach (array_slice($photos, 0, 2) as $photo)
                                            @if (!empty($photo['dataUri']))
                                                <img src="{{ $photo['dataUri'] }}" alt="Photo">
                                            @else
                                                <span class="muted">Image unavailable</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No performance records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
