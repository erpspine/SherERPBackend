<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Driver Profile - {{ $driver['name'] }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        @page { margin: 10mm; }
        .top-band { height: 10px; background: #c9a236; border-bottom: 2px solid #e5252a; }
        .content { padding: 16px 18px; }
        .header { width: 100%; margin-bottom: 22px; }
        .header td { padding: 0; border: 0; font-weight: normal; }
        .logo { width: 110px; max-height: 110px; }
        .brand-title { font-size: 22px; font-weight: 800; color: #32595a; letter-spacing: 0.6px; line-height: 1; }
        .brand-tagline { margin-top: 5px; font-size: 10.5px; color: #e5252a; font-style: italic; font-weight: 700; }
        .doc-heading { margin-top: 10px; display: inline-block; font-size: 11px; font-weight: 700; color: #ffffff; background: #32595a; padding: 6px 12px; border-left: 4px solid #c9a236; letter-spacing: 0.5px; }
        .contact-line { margin-top: 8px; font-size: 9px; color: #5d6b6b; }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
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
    <div class="top-band"></div>
    <div class="content">
    <table class="header">
        <tr>
            <td style="width:68%;">
                <div class="brand-title">{{ strtoupper($company) }}</div>
                <div class="brand-tagline">Conquer the wild</div>
                <div class="doc-heading">DRIVER PROFILE</div>
                <div class="contact-line">
                    523 Engutoto, Dharam Singh Road, Njiro Industrial Area, P.O Box 613, Arusha, Tanzania | +255
                    683 555 666 | info@sher.co.tz
                </div>
            </td>
            <td style="width:32%;text-align:right;">
                @if (!empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                @endif
            </td>
        </tr>
    </table>

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
                <th>Vehicle</th>
                <td>{{ $driver['vehicleText'] ?: '—' }}</td>
            </tr>
            <tr>
                <th>Language</th>
                <td>{{ $driver['languageText'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Driver Licence</th>
                <td>{{ $driver['driverLicense'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Tour Guide Licence</th>
                <td>{{ $driver['tourGuideLicense'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Contract Type</th>
                <td>{{ $driver['contractType'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Contract From</th>
                <td>{{ $driver['contractStartDate'] ?: '-' }}</td>
            </tr>
            <tr>
                <th>Contract To</th>
                <td>{{ $driver['contractEndDate'] ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated at {{ $driver['generatedAt'] }}.
    </div>
    </div>
</body>

</html>
