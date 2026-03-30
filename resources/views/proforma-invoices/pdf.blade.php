<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Proforma Invoice {{ $proformaInvoice['id'] }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2a2a;
            font-size: 10.5px;
            background: #ececec;
        }

        * {
            box-sizing: border-box;
        }

        .sheet {
            width: 100%;
            min-height: 1010px;
            background: #ffffff;
            border: 1px solid #d6d6d6;
            position: relative;
            overflow: hidden;
        }

        .top-band {
            height: 10px;
            background: #c9a236;
            border-bottom: 2px solid #e5252a;
        }

        .watermark {
            position: absolute;
            width: 320px;
            height: 320px;
            right: -100px;
            top: 130px;
            border: 20px solid rgba(50, 89, 90, 0.04);
            border-radius: 50%;
            pointer-events: none;
        }

        .content {
            padding: 16px 18px;
            position: relative;
            z-index: 2;
        }

        /* ── Header ── */
        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 110px;
            max-height: 110px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: #32595a;
            letter-spacing: 0.6px;
            line-height: 1;
        }

        .brand-tagline {
            margin-top: 5px;
            font-size: 10.5px;
            color: #e5252a;
            font-style: italic;
            font-weight: 700;
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
            font-size: 9.5px;
            color: #5d6b6b;
        }

        /* ── Meta block ── */
        .meta-outer {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta-outer td {
            vertical-align: top;
        }

        .to-block {
            border: 1px solid #d6d6d6;
            border-left: 4px solid #c9a236;
            padding: 8px 10px;
            background: #f8f8f8;
            font-size: 10.5px;
            line-height: 1.6;
        }

        .to-block .to-label {
            font-weight: 700;
            color: #32595a;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .to-block .to-name {
            font-size: 12px;
            font-weight: 700;
            color: #1f2a2a;
        }

        .ref-block {
            border: 1px solid #d6d6d6;
            padding: 8px 10px;
            background: #f8f8f8;
            font-size: 10px;
            line-height: 1.8;
        }

        .ref-label {
            font-weight: 700;
            color: #32595a;
        }

        /* ── Section title ── */
        .section-title {
            margin: 12px 0 6px;
            padding: 6px 9px;
            background: #f8f8f8;
            border: 1px solid #d6d6d6;
            border-left: 4px solid #c9a236;
            font-size: 10.5px;
            font-weight: 700;
            color: #32595a;
        }

        /* ── Items table ── */
        .table-wrap {
            border: 1px solid #c0c0c0;
            margin-bottom: 8px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items th,
        .items td {
            border-right: 1px solid #c0c0c0;
            border-bottom: 1px solid #c0c0c0;
            padding: 5px 6px;
            font-size: 9.5px;
            vertical-align: top;
            word-break: break-word;
        }

        .items th:last-child,
        .items td:last-child {
            border-right: 0;
        }

        .items tr:last-child td {
            border-bottom: 0;
        }

        .items th {
            background: #f4efe3;
            color: #32595a;
            font-weight: 700;
            text-align: left;
        }

        .day-row td {
            background: #fdf6e8;
            font-weight: 700;
            font-size: 10px;
            color: #2d4243;
            border-bottom: 1px solid #d8c89a;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }

        /* ── Totals ── */
        .totals {
            width: 280px;
            margin-left: auto;
            margin-top: 8px;
            border-collapse: collapse;
        }

        .totals td {
            border: 1px solid #c0c0c0;
            padding: 6px 9px;
            font-size: 10px;
        }

        .totals .grand td {
            background: #f4efe3;
            font-weight: 700;
            font-size: 11px;
            color: #32595a;
        }

        /* ── Notes ── */
        .notes-box {
            margin-top: 10px;
            border: 1px solid #d6d6d6;
            background: #fcfcfc;
            padding: 8px 10px;
            font-size: 10px;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
        }

        /* ── Closing / signature ── */
        .closing {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .closing td {
            vertical-align: top;
            font-size: 10px;
        }

        .sign-label {
            font-weight: 700;
            color: #e5252a;
            margin-bottom: 2px;
        }

        .sign-line {
            margin-top: 34px;
            border-top: 1px solid #1f2937;
            width: 84%;
            padding-top: 4px;
            font-size: 9.5px;
            color: #374151;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px dashed #d6d6d6;
            font-size: 9px;
            color: #6b7280;
        }

        /* ── PI notice band ── */
        .pi-notice {
            margin-bottom: 10px;
            padding: 6px 10px;
            background: #fff8e6;
            border: 1px solid #e0c97a;
            font-size: 10px;
            color: #7a5a00;
        }
    </style>
</head>

<body>
    @php
        $currency = $company['currency'] ?? 'TZS';
        $piNo = 'PI-' . now()->format('Y') . '-' . str_pad($proformaInvoice['id'], 4, '0', STR_PAD_LEFT);
    @endphp

    <div class="sheet">
        <div class="top-band"></div>
        <div class="watermark"></div>

        <div class="content">

            {{-- ── Header ── --}}
            <table class="header">
                <tr>
                    <td style="width:68%;">
                        <div class="brand-title">{{ strtoupper($company['name'] ?? config('app.name')) }}</div>
                        <div class="brand-tagline">Conquer the wild</div>
                        <div class="doc-heading">PROFORMA INVOICE</div>
                        <div class="contact-line">
                            {{ $company['address'] ?? '' }}
                            {{ !empty($company['phone'])  ? ' | ' . $company['phone']  : '' }}
                            {{ !empty($company['email'])  ? ' | ' . $company['email']  : '' }}
                        </div>
                    </td>
                    <td style="width:32%;text-align:right;vertical-align:top;">
                        @if (!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        @endif
                    </td>
                </tr>
            </table>

            {{-- ── PI Notice ── --}}
            <div class="pi-notice">
                This is a <strong>Proforma Invoice</strong>. Payment is due upon acceptance of terms. This document does not
                constitute a tax invoice.
            </div>

            {{-- ── To / Reference ── --}}
            <table class="meta-outer">
                <tr>
                    <td style="width:58%;padding-right:10px;">
                        <div class="to-block">
                            <div class="to-label">Bill To</div>
                            <div class="to-name">{{ $proformaInvoice['client'] }}</div>
                            @if (!empty($proformaInvoice['attention']))
                                <div>Attn: {{ $proformaInvoice['attention'] }}</div>
                            @endif
                            @if (!empty($company['address']))
                                <div style="color:#5d6b6b;font-size:9.5px;">{{ $company['address'] }}</div>
                            @endif
                        </div>
                    </td>
                    <td style="width:42%;">
                        <div class="ref-block">
                            <span class="ref-label">PI No:</span> {{ $piNo }}<br>
                            <span class="ref-label">Date:</span> {{ $proformaInvoice['quoteDate'] ?? now()->format('Y-m-d') }}<br>
                            @if (!empty($proformaInvoice['quotationId']))
                                <span class="ref-label">Quotation Ref:</span> QT-{{ now()->format('Y') }}-{{ str_pad($proformaInvoice['quotationId'], 4, '0', STR_PAD_LEFT) }}<br>
                            @endif
                            @if (!empty($proformaInvoice['leadId']))
                                <span class="ref-label">Lead Ref:</span> #{{ $proformaInvoice['leadId'] }}<br>
                            @endif
                            <span class="ref-label">Status:</span>
                            <span style="color:#c9a236;font-weight:700;">{{ $proformaInvoice['status'] }}</span>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- ── Dear / Intro ── --}}
            <div style="font-size:10.5px;line-height:1.6;margin-bottom:10px;">
                Dear {{ $proformaInvoice['attention'] ?? $proformaInvoice['client'] }},<br>
                Please find below our proforma invoice for the services agreed upon.
            </div>

            {{-- ── Line Items ── --}}
            <div class="section-title">Service Details</div>
            <div class="table-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width:5%;"  class="text-center">#</th>
                            <th style="width:13%;">Type</th>
                            <th style="width:32%;">Description</th>
                            <th style="width:9%;"  class="text-center">Unit</th>
                            <th style="width:7%;"  class="text-right">Qty</th>
                            <th style="width:17%;" class="text-right">Rate ({{ $currency }})</th>
                            <th style="width:17%;" class="text-right">Total ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grouped = collect($proformaInvoice['lineItems'])->groupBy('dayTitle');
                            $rowNum  = 1;
                        @endphp
                        @forelse ($grouped as $dayTitle => $dayItems)
                            @php $firstItem = $dayItems->first(); @endphp
                            <tr class="day-row">
                                <td colspan="7">
                                    {{ $dayTitle }}@if (!empty($firstItem['dayDescription']))
                                        &nbsp;&mdash;&nbsp;{{ $firstItem['dayDescription'] }}
                                    @endif
                                </td>
                            </tr>
                            @foreach ($dayItems as $item)
                                <tr>
                                    <td class="text-center">{{ $rowNum++ }}</td>
                                    <td>{{ $item['item'] }}</td>
                                    <td>{{ $item['description'] }}</td>
                                    <td class="text-center">{{ $item['unit'] }}</td>
                                    <td class="text-right">{{ $item['qty'] }}</td>
                                    <td class="text-right">{{ number_format((float) $item['rate'], 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $item['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No line items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── Totals ── --}}
            <table class="totals">
                <tr>
                    <td>Subtotal ({{ $currency }})</td>
                    <td class="text-right">{{ number_format((float) $proformaInvoice['subtotal'], 2) }}</td>
                </tr>
                <tr>
                    <td>Tax ({{ $company['vat'] ?? '0' }}%)</td>
                    <td class="text-right">{{ number_format((float) $proformaInvoice['tax'], 2) }}</td>
                </tr>
                <tr class="grand">
                    <td>Total ({{ $currency }})</td>
                    <td class="text-right">{{ number_format((float) $proformaInvoice['total'], 2) }}</td>
                </tr>
            </table>

            {{-- ── Notes ── --}}
            @if (!empty($proformaInvoice['notes']))
                <div class="section-title">Notes</div>
                <div class="notes-box">{{ $proformaInvoice['notes'] }}</div>
            @endif

            {{-- ── Payment Terms ── --}}
            <div class="section-title">Payment Terms</div>
            <div class="notes-box">Payment is due upon confirmation of this proforma invoice.
Please quote the PI number <strong>{{ $piNo }}</strong> in all remittances.</div>

            {{-- ── Closing ── --}}
            <table class="closing">
                <tr>
                    <td style="width:55%;">
                        <p style="margin:0 0 6px;">Please confirm acceptance of this proforma invoice to proceed.</p>
                        <p style="margin:0;">We appreciate your continued partnership.</p>
                        <div style="margin-top:30px;">
                            <div class="sign-label">Authorised Signatory</div>
                            <div class="sign-line">Name &amp; Signature</div>
                            <div style="margin-top:10px;">Date: ____________________</div>
                        </div>
                    </td>
                    <td style="width:45%;text-align:right;vertical-align:bottom;">
                        <div style="font-size:10px;color:#5d6b6b;">
                            {{ $company['phone'] ?? '' }}<br>
                            {{ $company['email'] ?? '' }}<br>
                            @if (!empty($company['tax_registration_number']))
                                TIN: {{ $company['tax_registration_number'] }}
                            @endif
                        </div>
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
