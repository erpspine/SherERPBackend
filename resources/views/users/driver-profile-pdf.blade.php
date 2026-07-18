<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Profile - {{ $driver['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.45;
            margin: 0;
            padding: 28px;
            background: #ffffff;
        }
        .header {
            border-bottom: 3px solid #d97706;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .company {
            color: #92400e;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        h1 {
            font-size: 26px;
            margin: 4px 0 0;
            color: #111827;
        }
        .subtitle {
            color: #64748b;
            margin-top: 4px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        th {
            width: 32%;
            text-align: left;
            color: #475569;
            background: #f8fafc;
            font-weight: 700;
        }
        td {
            color: #0f172a;
            font-weight: 600;
        }
        tr:last-child th,
        tr:last-child td {
            border-bottom: 0;
        }
        .footer {
            margin-top: 22px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">{{ $company }}</div>
        <h1>Driver Profile</h1>
        <div class="subtitle">Prepared for client sharing</div>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Driver Name</th>
                <td>{{ $driver['name'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $driver['phone'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $driver['email'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Language</th>
                <td>{{ $driver['languageText'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Experience</th>
                <td>{{ $driver['experience'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Driving Start Date</th>
                <td>{{ $driver['drivingStartedAt'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Driver Licence</th>
                <td>{{ $driver['driverLicense'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Tour Guide Licence</th>
                <td>{{ $driver['tourGuideLicense'] ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated at {{ $driver['generatedAt'] }}.
    </div>
</body>
</html>
