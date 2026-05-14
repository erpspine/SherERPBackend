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
            font-size: 11px;
            color: #1f2937;
        }

        .header {
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .sub {
            color: #4b5563;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #f3f4f6;
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
    </style>
</head>

<body>
    <div class="header">
        <div class="title">Vehicle Availability Report</div>
        <div class="sub">Month: {{ $monthLabel }} | Generated at: {{ $generatedAt }}</div>
    </div>

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
</body>

</html>
