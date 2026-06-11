<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Lease Proforma Invoice {{ $leaseProformaInvoice['proformaNumber'] }}</title>
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
            white-space: nowrap;
        }

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

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

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

        .terms-box {
            margin-top: 6px;
            padding: 6px 8px;
            line-height: 1.25;
            white-space: normal;
        }

        .terms-box .term-heading {
            margin: 0 0 3px;
            font-weight: 700;
        }

        .terms-box .term-line {
            margin: 0 0 2px;
        }

        .terms-box .term-spacer {
            height: 3px;
        }

        .bank-details {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 10px;
        }

        .bank-details td {
            border: 1px solid #d6d6d6;
            padding: 6px 8px;
        }

        .bank-label {
            width: 35%;
            font-weight: 700;
            color: #32595a;
            background: #f8f8f8;
        }

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

        .sign-box {
            margin-top: 20px;
            border-top: 1px solid #aaa;
            width: 160px;
            padding-top: 4px;
            color: #5d6b6b;
        }

        .footer-note {
            margin-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #667;
        }
    </style>
</head>

<body>
    @php
        $currency = strtoupper($leaseProformaInvoice['currency'] ?? 'USD');
        $money = fn($value) => $currency . ' ' . number_format((float) $value, 2, '.', ',');
        $formatDate = function ($value) {
            if (empty($value)) {
                return now()->format('d/m/Y');
            }

            $raw = trim((string) $value);
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
                return $raw;
            }

            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $matches)) {
                return $matches[3] . '/' . $matches[2] . '/' . $matches[1];
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };
    @endphp

    <div class="sheet">
        <div class="top-band"></div>
        <div class="watermark"></div>

        <div class="content">
            <table class="header">
                <tr>
                    <td style="width: 130px;">
                        @if (!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        @endif
                    </td>
                    <td>
                        <div class="brand-title">{{ strtoupper($company['name'] ?? config('app.name')) }}</div>
                        <div class="brand-tagline">Long term lease proforma invoice</div>
                        <div class="doc-heading">LEASE PROFORMA INVOICE</div>
                        <div class="contact-line">
                            @if (!empty($company['address']))
                                {{ $company['address'] }}
                            @endif
                            @if (!empty($company['phone']))
                                | {{ $company['phone'] }}
                            @endif
                            @if (!empty($company['email']))
                                | {{ $company['email'] }}
                            @endif
                        </div>
                    </td>
                    <td style="width: 150px; text-align: right;">
                        <div class="to-block" style="display: inline-block; min-width: 150px; text-align: left;">
                            <div class="to-label">Proforma No.</div>
                            <div class="to-name">{{ $leaseProformaInvoice['proformaNumber'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="meta-outer">
                <tr>
                    <td style="width: 58%; padding-right: 8px;">
                        <div class="to-block">
                            <div class="to-label">Bill To</div>
                            <div class="to-name">{{ $leaseProformaInvoice['clientName'] }}</div>
                            @if (!empty($leaseProformaInvoice['attention']))
                                <div>Attention: {{ $leaseProformaInvoice['attention'] }}</div>
                            @endif
                            @if (!empty($leaseProformaInvoice['contract']['leaseType']))
                                <div>Contract: {{ $leaseProformaInvoice['contract']['leaseType'] }}</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="ref-block">
                            <div><span class="ref-label">Proforma No:</span>
                                {{ $leaseProformaInvoice['proformaNumber'] }}</div>
                            <div><span class="ref-label">Invoice Date:</span>
                                {{ $formatDate($leaseProformaInvoice['invoiceDate'] ?? null) }}</div>
                            <div><span class="ref-label">Currency:</span> {{ $currency }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="section-title">Line Items</div>

            <div class="table-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Description</th>
                            <th style="width: 15%;" class="text-center">No. of Vehicles</th>
                            <th style="width: 15%;" class="text-center">No. of Days</th>
                            <th style="width: 15%;" class="text-right">Rate</th>
                            <th style="width: 15%;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaseProformaInvoice['lineItems'] ?? [] as $item)
                            <tr>
                                <td>{{ $item['description'] ?? '' }}</td>
                                <td class="text-center">{{ number_format((float) ($item['noVehicles'] ?? 0), 0) }}</td>
                                <td class="text-center">{{ number_format((float) ($item['noDays'] ?? 0), 0) }}</td>
                                <td class="text-right">{{ $money($item['rate'] ?? 0) }}</td>
                                <td class="text-right">{{ $money($item['total'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <table class="totals">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">{{ $money($leaseProformaInvoice['subtotal'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td>Tax</td>
                    <td class="text-right">{{ $money($leaseProformaInvoice['tax'] ?? 0) }}</td>
                </tr>
                <tr class="grand">
                    <td>Total</td>
                    <td class="text-right">{{ $money($leaseProformaInvoice['total'] ?? 0) }}</td>
                </tr>
            </table>

            @if (!empty($leaseProformaInvoice['notes']))
                <div class="section-title">Notes</div>
                <div class="notes-box">{{ $leaseProformaInvoice['notes'] }}</div>
            @endif

            <div class="section-title">Payment Terms</div>
            <div class="notes-box terms-box">
                <div class="term-heading">PAYMENT TERMS</div>
                <div class="term-line">A non-refundable deposit of 30% shall be payable upon issuance and confirmation
                    of the Proforma Invoice.</div>
                <div class="term-line">Full and final payment shall be made no later than fourteen (14) days prior to
                    commencement date.</div>
                <div class="term-line">No refunds shall be issued for cancellations made within thirty (30) days prior
                    to commencement date.</div>
                <div class="term-spacer"></div>
                <div class="term-line">Deployment of any vehicle is strictly conditional upon receipt of cleared funds.
                </div>
            </div>

            @php
                $invoiceCurrency = strtoupper($leaseProformaInvoice['currency'] ?? 'USD');
                $normalizedCurrency = in_array($invoiceCurrency, ['TSH', 'TZS'], true) ? 'TZS' : 'USD';
                $bankAccountNo = $normalizedCurrency === 'TZS' ? '010000225378' : '010010003888';
            @endphp
            <div class="section-title">Bank Details</div>
            <table class="bank-details">
                <tr>
                    <td class="bank-label">Bank Name</td>
                    <td>Azania Bank Plc</td>
                </tr>
                <tr>
                    <td class="bank-label">Account Name</td>
                    <td>Sher East Africa Limited</td>
                </tr>
                <tr>
                    <td class="bank-label">Account No.</td>
                    <td>{{ $bankAccountNo }}</td>
                </tr>
                <tr>
                    <td class="bank-label">Currency</td>
                    <td>{{ $normalizedCurrency }}</td>
                </tr>
                <tr>
                    <td class="bank-label">Swift Code</td>
                    <td>AZANTZTZ</td>
                </tr>
            </table>

            <table class="closing">
                <tr>
                    <td style="width: 50%;">
                        <div class="sign-label">Prepared By</div>
                        <div class="sign-box">Authorized Signature</div>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="sign-label">Customer Acceptance</div>
                        <div class="sign-box" style="margin-left: auto;">Name / Signature / Date</div>
                    </td>
                </tr>
            </table>

            <div class="footer-note">
                This is a computer generated document and does not require a signature.
            </div>
        </div>
    </div>
</body>

</html>
