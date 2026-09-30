<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $number }} — Per Diem Allowance Voucher</title>
    <style>
        @page { margin: 5mm; }
        body { margin: 0; line-height: 1.1; font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1f2a2a; }
        .letterhead { border-top: 4px solid #c9a236; border-bottom: 1px solid #e5252a; padding: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 2px 3px; }
        .brand { font-size: 14px; font-weight: bold; color: #32595a; }
        .tagline { color: #e5252a; font-style: italic; margin: 2px 0; }
        .logo { max-width: 55px; max-height: 38px; }
        h1 { font-size: 10px; color: #32595a; text-align: center; margin: 4px 0 3px; }
        .meta { margin-bottom: 4px; }
        .meta td { width: 50%; border: 1px solid #ddd; }
        .items th { background: #32595a; color: white; text-align: left; }
        .items td { border: 1px solid #ddd; overflow-wrap: break-word; }
        .amount { text-align: right; white-space: nowrap; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .signatures { page-break-inside: avoid; margin-top: 4px; }
        p { margin: 3px 0; }
        h2 { font-size: 9px; margin: 4px 0 3px; padding: 2px 3px; border-left: 2px solid #c9a236; background: #f3f5f5; }
        .signature-line { margin-top: 8px; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <table><tr><td>
            <div class="brand">{{ strtoupper($company['name'] ?? config('app.name')) }}</div>
            <div class="tagline">Conquer the wild</div>
            <div>{{ $company['address'] ?: '523 Engutoto, Dharam Singh Road, Njiro Industrial Area, P.O Box 613, Arusha, Tanzania' }}</div>
            <div>{{ $company['phone'] ?: '+255 683 555 666' }} | {{ $company['email'] ?: 'info@sher.co.tz' }}</div>
        </td><td style="width:80px;text-align:right;">
            @if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Company logo">@endif
        </td></tr></table>
    </div>
    <h1>DRIVER GUIDE PER DIEM ALLOWANCE VOUCHER</h1>
    <p><strong>Voucher No: {{ $number }}</strong></p>
    <table class="meta">
        <tr><td><strong>Jobcard Number:</strong> {{ $voucher['jobCardNo'] }}</td><td><strong>Date Created:</strong> {{ $voucher['createdAt'] }}</td></tr>
        <tr><td><strong>Driver Name:</strong> {{ $voucher['driverName'] }}</td><td><strong>Vehicle Plate No:</strong> {{ !empty($voucher['vehiclePlateNo'] ?? null) ? $voucher['vehiclePlateNo'] : '—' }}</td></tr>
        <tr><td><strong>Client:</strong> {{ !empty($voucher['clientName'] ?? null) ? $voucher['clientName'] : '—' }}</td><td><strong>Group Name:</strong> {{ $voucher['groupName'] ?: '—' }}</td></tr>
        <tr><td><strong>Safari Start:</strong> {{ $voucher['safariStartDate'] ?: '—' }}</td><td><strong>Safari End:</strong> {{ $voucher['safariEndDate'] ?: '—' }}</td></tr>
    </table>
    <table class="items">
        <thead><tr><th style="width:22%;">Date / Day</th><th>Itinerary</th><th style="width:22%;text-align:right;">Amount</th></tr></thead>
        <tbody>
            @foreach($voucher['items'] as $item)
                <tr><td>{{ $item['date'] }}</td><td>{!! nl2br(e($item['dayDescription'])) !!}</td><td class="amount">{{ number_format($item['allowancePerDay'], 2) }}</td></tr>
            @endforeach
            <tr><td colspan="2"><strong>Total Amount</strong></td><td class="amount"><strong>{{ number_format($voucher['total'], 2) }}</strong></td></tr>
        </tbody>
    </table>
    <div class="signatures">
        <h2>Prepared By</h2>
        <p>Name: {{ $voucher['preparedBy'] }}</p>
        <p class="signature-line">Signature: _______________________ &nbsp; Date: __________________</p>
        <h2>Recipient Acknowledgement</h2>
        <p>I hereby acknowledge that I have received the total amount stated above as allowance for the specified safari trip.</p>
        <p>Driver Name: {{ $voucher['driverName'] }}</p>
        <p class="signature-line">Signature: _______________________ &nbsp; Date: __________________</p>
    </div>
</body>
</html>
