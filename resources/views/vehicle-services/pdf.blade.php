<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Services Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .header {
            margin-bottom: 14px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .meta {
            color: #6b7280;
            font-size: 10px;
            margin-top: 2px;
        }

        .stats {
            margin: 12px 0;
            width: 100%;
            border-collapse: collapse;
        }

        .stats td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            font-size: 10px;
        }

        .stats .label {
            color: #6b7280;
            width: 140px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th,
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 6px;
            vertical-align: top;
        }

        table.data th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #4b5563;
            text-align: left;
        }

        .muted {
            color: #6b7280;
        }

        ul.items {
            margin: 0;
            padding-left: 14px;
        }

        ul.items li {
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Vehicle Services Report</p>
        <p class="meta">
            {{ $company['name'] ?? config('app.name') }}
            @if(!empty($company['email'])) | {{ $company['email'] }} @endif
            @if(!empty($company['phone'])) | {{ $company['phone'] }} @endif
        </p>
        <p class="meta">Generated: {{ $generatedAt }}</p>
    </div>

    <table class="stats">
        <tr>
            <td class="label">Total Records</td>
            <td>{{ $stats['total'] ?? 0 }}</td>
            <td class="label">In Service</td>
            <td>{{ $stats['inService'] ?? 0 }}</td>
            <td class="label">Returned</td>
            <td>{{ $stats['returned'] ?? 0 }}</td>
            <td class="label">Cancelled</td>
            <td>{{ $stats['cancelled'] ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Total Cost</td>
            <td colspan="7">USD {{ number_format((float)($stats['totalCost'] ?? 0), 2) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Date</th>
                <th>Service Center</th>
                <th>Service Type</th>
                <th>Line Items</th>
                <th>Odo In</th>
                <th>Cost</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['vehicleNo'] ?: '-' }}</strong><br>
                        <span class="muted">{{ $row['plateNo'] ?: '-' }}</span>
                    </td>
                    <td>{{ $row['serviceDate'] ?: '-' }}</td>
                    <td>{{ $row['serviceCenter'] ?: '-' }}</td>
                    <td>{{ $row['serviceType'] ?: '-' }}</td>
                    <td>
                        @if(!empty($row['serviceItems']) && is_array($row['serviceItems']))
                            <ul class="items">
                                @foreach($row['serviceItems'] as $item)
                                    <li>
                                        {{ $item['service'] ?? '-' }}
                                        @if(!empty($item['details']))
                                            ({{ $item['details'] }})
                                        @endif
                                        @if(isset($item['cost']) && $item['cost'] !== null)
                                            - USD {{ number_format((float)$item['cost'], 2) }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <span class="muted">-</span>
                        @endif
                    </td>
                    <td>{{ $row['odometerIn'] ?? '-' }}</td>
                    <td>
                        @if($row['cost'] !== null && $row['cost'] !== '')
                            USD {{ number_format((float)$row['cost'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $row['status'] ?: '-' }}</td>
                    <td>{{ $row['notes'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;" class="muted">No vehicle service records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
