<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Safari Allocations Report</title>
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
                        <div class="doc-heading">SAFARI ALLOCATIONS REPORT</div>
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
                        {{ $filters['dateFrom'] ? \Illuminate\Support\Carbon::parse($filters['dateFrom'])->format('d F Y') : 'Start' }}
                        to
                        {{ $filters['dateTo'] ? \Illuminate\Support\Carbon::parse($filters['dateTo'])->format('d F Y') : 'End' }}<br>
                        Status: {{ $filters['status'] ?: 'All' }}<br>
                        Vehicle: {{ $filters['vehicle'] ?? 'All' }}<br>
                        Dates clipped: {{ !empty($filters['clipOverlapDates']) ? 'Yes' : 'No' }}<br>
                        Search: {{ $filters['search'] ?: 'All' }}
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
                        <div class="label">Assigned</div>
                        <div class="value">{{ $stats['assigned'] }}</div>
                    </td>
                    <td>
                        <div class="label">In Progress</div>
                        <div class="value">{{ $stats['inProgress'] }}</div>
                    </td>
                    <td>
                        <div class="label">Completed</div>
                        <div class="value">{{ $stats['completed'] }}</div>
                    </td>
                </tr>
            </table>

            <div class="section-title">Safari Allocation Records</div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Booking Ref</th>
                        <th style="width: 11%;">Client</th>
                        <th style="width: 10%;">Group Name</th>
                        <th style="width: 10%;">Vehicle</th>
                        <th style="width: 11%;">Driver</th>
                        <th style="width: 10%;">Driving Licence</th>
                        <th style="width: 10%;">Guide Licence</th>
                        <th style="width: 9%;">Start Date</th>
                        <th style="width: 9%;">End Date</th>
                        <th style="width: 5%;">Days</th>
                        <th style="width: 7%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $lead = is_array($row['lead'] ?? null) ? $row['lead'] : [];
                            $vehicle = is_array($row['vehicle'] ?? null) ? $row['vehicle'] : [];
                            $driver = is_array($row['driver'] ?? null) ? $row['driver'] : [];
                            $startDate = (string) ($row['exportStartDate'] ?? ($row['startDate'] ?? ''));
                            $endDate = (string) ($row['exportEndDate'] ?? ($row['endDate'] ?? ''));
                            $displayStartDate =
                                $startDate !== '' ? \Illuminate\Support\Carbon::parse($startDate)->format('d F Y') : '';
                            $displayEndDate =
                                $endDate !== '' ? \Illuminate\Support\Carbon::parse($endDate)->format('d F Y') : '';
                            $utilizedDays = (int) ($row['utilizedDays'] ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $lead['bookingRef'] ?? '-' }}</td>
                            <td>{{ $lead['clientCompany'] ?? '-' }}</td>
                            <td>{{ $lead['groupName'] ?? '' }}</td>
                            <td>{{ $vehicle['vehicleNo'] ?? '-' }}</td>
                            <td>{{ $driver['name'] ?? '' }}</td>
                            <td>{{ ($driver['driverLicense'] ?? null) ?: '-' }}</td>
                            <td>{{ ($driver['guideLicense'] ?? null) ?: '-' }}</td>
                            <td>{{ $displayStartDate }}</td>
                            <td>{{ $displayEndDate }}</td>
                            <td>{{ $utilizedDays }}</td>
                            <td>{{ $row['status'] ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align:center;">No safari allocations found for the selected
                                filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
