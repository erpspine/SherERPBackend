<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Odometer Log Report - Trip #{{ $report['tripId'] }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 10px;
        }

        .header {
            border-bottom: 2px solid #d4af37;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .muted {
            color: #64748b;
            font-size: 9px;
        }

        .section-title {
            margin-top: 10px;
            margin-bottom: 6px;
            padding: 5px 7px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #d4af37;
            font-weight: 700;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .grid th,
        .grid td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            vertical-align: top;
        }

        .grid th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .small {
            font-size: 9px;
            color: #64748b;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">{{ $company['name'] ?? config('app.name') }}</div>
        <div class="muted">
            Odometer and Fuel Report - Trip #{{ $report['tripId'] }}
            @if (!empty($company['phone']))
                | {{ $company['phone'] }}
            @endif
            @if (!empty($company['email']))
                | {{ $company['email'] }}
            @endif
        </div>
        <div class="small">Generated at:
            {{ \Illuminate\Support\Carbon::parse($report['generatedAt'])->format('Y-m-d H:i') }}</div>
    </div>

    <div class="section-title">Trip Details</div>
    <table class="grid">
        <tr>
            <th>Booking Ref</th>
            <td>{{ $report['leadBookingRef'] ?: '-' }}</td>
            <th>Group Name</th>
            <td>{{ $report['groupName'] ?: '-' }}</td>
        </tr>
        <tr>
            <th>Client</th>
            <td>{{ $report['clientCompany'] ?: '-' }}</td>
            <th>Route</th>
            <td>{{ $report['routeParks'] ?: '-' }}</td>
        </tr>
        <tr>
            <th>Trip Dates</th>
            <td>{{ $report['tripStartDate'] ?: '-' }} to {{ $report['tripEndDate'] ?: '-' }}</td>
            <th>Driver</th>
            <td>{{ $report['driverName'] ?: '-' }}</td>
        </tr>
        <tr>
            <th>Vehicle</th>
            <td colspan="3">{{ $report['vehicleLabel'] ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Summary</div>
    <table class="grid">
        <tr>
            <th>Total Logs</th>
            <td>{{ $report['totalLogs'] }}</td>
            <th>First Reading</th>
            <td>{{ $report['firstReading'] ?? '-' }}</td>
            <th>Last Reading</th>
            <td>{{ $report['lastReading'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Distance Covered</th>
            <td>{{ $report['distanceCovered'] ?? '-' }} km</td>
            <th>Fuel Events</th>
            <td>{{ $report['totalFuelEvents'] }}</td>
            <th>Total Liters</th>
            <td>{{ number_format((float) $report['totalLiters'], 2) }}</td>
        </tr>
        <tr>
            <th>Total Fuel Cost</th>
            <td colspan="5">TZS {{ number_format((float) $report['totalFuelCost'], 2) }}</td>
        </tr>
    </table>

    <div class="section-title">Log Entries</div>
    <table class="grid">
        <thead>
            <tr>
                <th class="nowrap">#</th>
                <th class="nowrap">Date/Time</th>
                <th>Type</th>
                <th>Location</th>
                <th class="right">Odometer</th>
                <th class="right">Liters</th>
                <th class="right">Unit Price</th>
                <th>Station</th>
                <th>Recorded By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $index => $log)
                <tr>
                    <td class="nowrap">{{ $index + 1 }}</td>
                    <td class="nowrap">
                        @if (!empty($log['recorded_at']))
                            {{ \Illuminate\Support\Carbon::parse($log['recorded_at'])->format('Y-m-d H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $log['entry_type'] ?? '-' }}</td>
                    <td>{{ $log['location'] ?? '-' }}</td>
                    <td class="right">{{ $log['odometer_reading'] ?? '-' }}</td>
                    <td class="right">{{ $log['liters'] !== null ? number_format((float) $log['liters'], 2) : '-' }}
                    </td>
                    <td class="right">
                        {{ $log['unit_price'] !== null ? number_format((float) $log['unit_price'], 2) : '-' }}</td>
                    <td>{{ $log['station'] ?: '-' }}</td>
                    <td>{{ $log['recorded_by'] ?: '-' }}</td>
                    <td>{{ $log['notes'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="small">No odometer logs recorded for this trip.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
