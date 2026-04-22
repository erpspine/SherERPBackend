<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inspection Checklist #{{ $inspection['id'] }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        :root {
            --brand-gold: #c9a236;
            --brand-teal: #32595a;
            --brand-red: #e5252a;
            --ink: #1f2a2a;
            --muted: #5d6b6b;
            --line: #d6d6d6;
            --panel: #f8f8f8;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: var(--ink);
            font-size: 10.5px;
            background: #ececec;
        }

        * {
            box-sizing: border-box;
        }

        .sheet {
            width: 100%;
            background: #fff;
            border: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }

        .top-band {
            height: 10px;
            background: var(--brand-gold);
            border-bottom: 2px solid var(--brand-red);
        }

        .content {
            padding: 14px;
            position: relative;
            z-index: 2;
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
            width: 110px;
            max-height: 110px;
            object-fit: contain;
            margin-top: -6px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--brand-teal);
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .heading {
            margin-top: 10px;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: var(--brand-teal);
            padding: 6px 10px;
            border-left: 4px solid var(--brand-gold);
            letter-spacing: 0.4px;
        }

        .contact-line {
            margin-top: 8px;
            font-size: 9.5px;
            color: var(--muted);
        }

        .section-title {
            margin-top: 12px;
            margin-bottom: 6px;
            padding: 6px 9px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-left: 4px solid var(--brand-gold);
            font-size: 10.5px;
            font-weight: 700;
            color: var(--brand-teal);
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .grid td,
        .grid th {
            border: 1px solid var(--line);
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10px;
        }

        .grid th {
            background: #f4efe3;
            color: var(--brand-teal);
            text-align: left;
            font-weight: 700;
        }

        .label {
            font-weight: 700;
            color: #2d4243;
        }

        .remarks {
            border: 1px solid var(--line);
            background: #fcfcfc;
            min-height: 60px;
            padding: 8px;
            white-space: pre-wrap;
            font-size: 10px;
            margin-bottom: 8px;
        }

        .status-ok {
            color: #0f7b0f;
            font-weight: 700;
        }

        .status-nok {
            color: #c62828;
            font-weight: 700;
        }

        .images {
            width: 100%;
            border-collapse: collapse;
        }

        .images td {
            width: 50%;
            border: 1px solid var(--line);
            padding: 8px;
            vertical-align: top;
        }

        .img-box {
            border: 1px dashed #cfd8dc;
            min-height: 120px;
            text-align: center;
            padding: 6px;
            background: #fafafa;
        }

        .img-box img {
            max-width: 100%;
            max-height: 180px;
            object-fit: contain;
        }

        .img-caption {
            margin-top: 6px;
            font-size: 9px;
            color: #6b7280;
            word-break: break-all;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signatures td {
            width: 50%;
            vertical-align: top;
            padding-right: 14px;
            font-size: 10px;
        }

        .sign-label {
            font-weight: 700;
            color: var(--brand-red);
            margin-bottom: 2px;
        }

        .sign-line {
            margin-top: 34px;
            border-top: 1px solid #1f2937;
            width: 88%;
            padding-top: 4px;
            font-size: 9.5px;
            color: #374151;
        }

        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px dashed var(--line);
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="sheet">
        <div class="top-band"></div>
        <div class="content">
            <table class="header">
                <tr>
                    <td style="width:70%;">
                        <div class="brand-title">{{ strtoupper($company['name'] ?? config('app.name')) }}</div>
                        <div class="heading">INSPECTION CHECKLIST</div>
                        <div class="contact-line">
                            {{ $company['address'] ?? '' }}
                            {{ !empty($company['phone']) ? ' | ' . $company['phone'] : '' }}
                            {{ !empty($company['email']) ? ' | ' . $company['email'] : '' }}
                        </div>
                    </td>
                    <td style="width:30%;text-align:right;">
                        @if (!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="section-title">Inspection Information</div>
            <table class="grid">
                <tr>
                    <td><span class="label">Inspection ID:</span> #{{ $inspection['id'] }}</td>
                    <td><span class="label">Type:</span>
                        {{ str_replace('_', ' ', ucfirst($inspection['type'] ?? '')) }}</td>
                    <td><span class="label">Date:</span>
                        {{ !empty($inspection['createdAt']) ? \Illuminate\Support\Carbon::parse($inspection['createdAt'])->format('Y-m-d H:i') : now()->format('Y-m-d H:i') }}
                    </td>
                </tr>
                <tr>
                    <td><span class="label">Client:</span> {{ $inspection['lead']['clientCompany'] ?? '-' }}</td>
                    <td><span class="label">Booking Ref:</span> {{ $inspection['lead']['bookingRef'] ?? '-' }}</td>
                    <td><span class="label">Vehicle:</span>
                        {{ trim(($inspection['vehicle']['make'] ?? '') . ' ' . ($inspection['vehicle']['model'] ?? '')) ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="3"><span class="label">Plate No:</span>
                        {{ $inspection['vehicle']['plateNo'] ?? '-' }}</td>
                </tr>
            </table>

            <div class="section-title">Checklist Items</div>
            @forelse($groupedItems as $group)
                <table class="grid" style="margin-bottom:10px;">
                    <thead>
                        <tr>
                            <th colspan="4">{{ $group['title'] }}</th>
                        </tr>
                        <tr>
                            <th style="width:6%;">#</th>
                            <th style="width:42%;">Item</th>
                            <th style="width:12%;">Status</th>
                            <th>Issue / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['items'] as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item['text'] ?? ($item['name'] ?? '-') }}</td>
                                <td>
                                    @if (($item['status'] ?? '') === 'OK')
                                        <span class="status-ok">OK</span>
                                    @else
                                        <span class="status-nok">NOK</span>
                                    @endif
                                </td>
                                <td>{{ $item['issue'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @empty
                <table class="grid">
                    <tr>
                        <td>No checklist items recorded.</td>
                    </tr>
                </table>
            @endforelse

            <div class="section-title">General Remarks</div>
            <div class="remarks">{{ $inspection['remarks'] ?? '-' }}</div>

            <div class="section-title">Inspection Photos</div>
            @if (count($images) > 0)
                <table class="images">
                    @foreach (array_chunk($images, 2) as $imageRow)
                        <tr>
                            @foreach ($imageRow as $image)
                                <td>
                                    <div class="img-box">
                                        @if (!empty($image['dataUri']))
                                            <img src="{{ $image['dataUri'] }}" alt="Inspection photo">
                                        @else
                                            <div>Image not available for PDF embedding.</div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            @if (count($imageRow) < 2)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <table class="grid">
                    <tr>
                        <td>No inspection photos attached.</td>
                    </tr>
                </table>
            @endif

            <table class="signatures">
                <tr>
                    <td>
                        <div class="sign-label">Manager Approval</div>
                        <div class="sign-line">Manager Name & Signature</div>
                        <div style="margin-top:10px;">Date: ____________________</div>
                    </td>
                    <td>
                        <div class="sign-label">Driver Confirmation</div>
                        <div class="sign-line">Driver Name & Signature</div>
                        <div style="margin-top:10px;">Date: ____________________</div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                Generated on {{ now()->format('Y-m-d H:i') }}
            </div>
        </div>
    </div>
</body>

</html>
