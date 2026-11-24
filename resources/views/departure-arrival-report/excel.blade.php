<html>
<head><meta charset="UTF-8"><style>
    body { font-family: Arial, sans-serif; }
    table { border-collapse: collapse; }
    td, th { border: 1px solid #b9c2c2; padding: 6px; }
    .brand { color: #32595a; font-size: 20px; font-weight: bold; }
    .title { color: #ffffff; background: #32595a; font-weight: bold; font-size: 14px; }
    .gold { background: #c9a236; height: 6px; }
    .summary { background: #f4efe3; font-weight: bold; }
    th { background: #32595a; color: #ffffff; font-weight: bold; }
</style></head>
<body>
<table>
    <tr><td colspan="10" class="gold"></td></tr>
    <tr><td colspan="10" class="brand">{{ $company['name'] ?? config('app.name') }}</td></tr>
    <tr><td colspan="10">{{ $company['address'] ?? '' }} @if (!empty($company['phone'])) | Tel: {{ $company['phone'] }} @endif @if (!empty($company['email'])) | Email: {{ $company['email'] }} @endif</td></tr>
    <tr><td colspan="10" class="title">DEPARTURE &amp; ARRIVAL REPORT</td></tr>
    <tr><td colspan="10">Generated: {{ $generatedAt }} | Period: {{ $filters['dateFrom'] ?: 'All dates' }} @if ($filters['dateTo']) to {{ $filters['dateTo'] }} @endif</td></tr>
    <tr class="summary"><td colspan="3">Movement entries: {{ $stats['total'] }}</td><td colspan="3">Departures: {{ $stats['departures'] }}</td><td colspan="4">Arrivals: {{ $stats['arrivals'] }}</td></tr>
    <tr><td colspan="10"></td></tr>
    <tr><th>Date</th><th>Movement</th><th>Vehicle</th><th>Driver</th><th>Contact</th><th>Group</th><th>Client</th><th>Job Card</th><th>Type</th><th>Status</th></tr>
    @foreach ($rows as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td><td>{{ $row['movement'] }}</td>
            <td>{{ $row['vehicle'] }}</td><td>{{ $row['driver'] }}</td><td>{{ $row['contact'] ?: '-' }}</td>
            <td>{{ $row['group'] }}</td><td>{{ $row['client'] }}</td><td>{{ $row['jobCard'] }}</td><td>{{ $row['type'] }}</td><td>{{ $row['status'] }}</td>
        </tr>
    @endforeach
</table>
</body>
</html>
