<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departure and Arrival Report</title>
    <style>
        @page { margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #1f2a2a; font-size: 9px; }
        .sheet { border: 1px solid #d6d6d6; }
        .top-band { height: 10px; background: #c9a236; border-bottom: 2px solid #e5252a; }
        .content { padding: 14px 16px; }
        .header, .summary, .report-table { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 12px; }
        .header td { vertical-align: top; }
        .logo { width: 100px; max-height: 85px; object-fit: contain; }
        .brand-title { font-size: 21px; font-weight: 800; color: #32595a; }
        .doc-heading { margin-top: 9px; display: inline-block; font-size: 11px; font-weight: 700; color: #fff; background: #32595a; padding: 6px 12px; border-left: 4px solid #c9a236; }
        .contact-line, .generated { margin-top: 7px; color: #5d6b6b; font-size: 8.5px; line-height: 1.5; }
        .generated { text-align: right; }
        .summary { margin-bottom: 12px; }
        .summary td { width: 33.33%; border: 1px solid #d6d6d6; background: #f8f8f8; padding: 7px 10px; }
        .summary .label { color: #32595a; font-weight: 700; text-transform: uppercase; font-size: 8px; }
        .summary .value { margin-top: 2px; font-size: 14px; font-weight: 800; }
        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th, .report-table td { border: 1px solid #c7cccc; padding: 5px; vertical-align: top; word-wrap: break-word; }
        .report-table th { background: #f4efe3; color: #32595a; text-align: left; font-size: 8px; }
        .departure { color: #a16207; font-weight: 700; }
        .arrival { color: #047857; font-weight: 700; }
        .empty { padding: 24px; text-align: center; color: #64748b; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="top-band"></div>
    <div class="content">
        <table class="header">
            <tr>
                <td style="width:120px">
                    @if ($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Sher logo">@endif
                </td>
                <td>
                    <div class="brand-title">{{ $company['name'] ?? config('app.name') }}</div>
                    <div class="doc-heading">DEPARTURE &amp; ARRIVAL REPORT</div>
                    <div class="contact-line">
                        {{ $company['address'] ?? '' }}
                        @if (!empty($company['phone'])) | Tel: {{ $company['phone'] }} @endif
                        @if (!empty($company['email'])) | Email: {{ $company['email'] }} @endif
                    </div>
                </td>
                <td class="generated" style="width:220px">
                    Generated: {{ $generatedAt }}<br>
                    Period:
                    {{ $filters['dateFrom'] ? \Carbon\Carbon::parse($filters['dateFrom'])->format('d F Y') : 'All dates' }}
                    @if ($filters['dateTo']) to {{ \Carbon\Carbon::parse($filters['dateTo'])->format('d F Y') }} @endif
                </td>
            </tr>
        </table>

        <table class="summary"><tr>
            <td><div class="label">Movement entries</div><div class="value">{{ $stats['total'] }}</div></td>
            <td><div class="label">Departures</div><div class="value">{{ $stats['departures'] }}</div></td>
            <td><div class="label">Arrivals</div><div class="value">{{ $stats['arrivals'] }}</div></td>
        </tr></table>

        <table class="report-table">
            <thead><tr>
                <th style="width:8%">Date</th><th style="width:8%">Movement</th><th style="width:11%">Vehicle</th>
                <th style="width:12%">Driver</th><th style="width:10%">Contact</th><th style="width:14%">Group</th>
                <th style="width:14%">Client</th><th style="width:10%">Job Card</th><th style="width:8%">Type</th><th style="width:5%">Status</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td class="{{ strtolower($row['movement']) }}">{{ $row['movement'] }}</td>
                    <td>{{ $row['vehicle'] }}</td><td>{{ $row['driver'] }}</td><td>{{ $row['contact'] ?: '-' }}</td>
                    <td>{{ $row['group'] }}</td><td>{{ $row['client'] }}</td><td>{{ $row['jobCard'] }}</td>
                    <td>{{ $row['type'] }}</td><td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty">No departure or arrival entries found for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
