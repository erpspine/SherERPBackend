<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2a2a;
            font-size: 8px;
        }

        .sheet {
            border: 1px solid #d6d6d6;
        }

        .top-band {
            height: 10px;
            background: #c9a236;
            border-bottom: 2px solid #e5252a;
        }

        .content {
            padding: 14px 16px;
        }

        .header,
        .summary,
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            margin-bottom: 12px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 100px;
            max-height: 72px;
            object-fit: contain;
        }

        .brand-title {
            font-size: 21px;
            font-weight: 800;
            color: #32595a;
            line-height: 1;
        }

        .doc-heading {
            margin-top: 10px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            background: #32595a;
            padding: 6px 12px;
            border-left: 4px solid #c9a236;
        }

        .contact-line,
        .generated {
            color: #5d6b6b;
            font-size: 8px;
            line-height: 1.6;
        }

        .generated {
            text-align: right;
        }

        .summary {
            margin-bottom: 10px;
        }

        .summary td {
            border: 1px solid #d6d6d6;
            background: #f8f8f8;
            padding: 7px 9px;
        }

        .summary .label {
            color: #32595a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7px;
        }

        .summary .value {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 800;
        }

        .section-title {
            margin: 10px 0 6px;
            padding: 6px 9px;
            background: #f8f8f8;
            border: 1px solid #d6d6d6;
            border-left: 4px solid #c9a236;
            font-size: 10px;
            font-weight: 700;
            color: #32595a;
        }

        .report-table {
            table-layout: fixed;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tr {
            page-break-inside: avoid;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #c0c0c0;
            padding: 4px 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .report-table th {
            background: #f4efe3;
            color: #32595a;
            font-weight: 700;
            text-align: left;
        }

        .status-good {
            color: #166534;
            font-weight: 700;
        }

        .status-warning {
            color: #92400e;
            font-weight: 700;
        }

        .status-deduction {
            color: #b91c1c;
            font-weight: 700;
        }
    </style>
</head>

<body>
    @php
        $displayDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;
    @endphp
    <div class="sheet">
        <div class="top-band"></div>
        <div class="content">
            <table class="header">
                <tr>
                    <td style="width:120px">
                        @if ($logoDataUri)
                            <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
                        @endif
                    </td>
                    <td>
                        <div class="brand-title">{{ $company['name'] ?? config('app.name') }}</div>
                        <div class="doc-heading">{{ $title }}</div>
                        <div class="contact-line">{{ $company['address'] ?? '' }} @if (!empty($company['phone']))
                                | Tel: {{ $company['phone'] }}
                                @endif @if (!empty($company['email']))
                                    | Email: {{ $company['email'] }}
                                @endif
                        </div>
                    </td>
                    <td class="generated" style="width:190px">Generated: {{ $generatedAt }}<br>Period:
                        {{ $displayDate($dateFrom) ?: 'Start' }} to {{ $displayDate($dateTo) ?: 'End' }}</td>
                </tr>
            </table>
            <table class="summary">
                @if ($summary)
                    <tr>
                        @foreach ([
                            'Total trips' => $summary['trips'],
                            'Total days' => $summary['days'],
                            'Consumed litres' => $summary['litres'],
                            'Total KM' => $summary['km'],
                            'Daily average' => $summary['daily_average'] !== null ? $summary['daily_average'] . ' km/day' : null,
                            'Total deductions' => $summary['deductions'],
                        ] as $label => $value)
                            <td>
                                <div class="label">{{ $label }}</div>
                                <div class="value">{{ $value === null || $value === '' ? '—' : (is_numeric($value) ? number_format($value, 2) : $value) }}</div>
                            </td>
                        @endforeach
                    </tr>
                @else
                    <tr>
                        <td>
                            <div class="label">Total records</div>
                            <div class="value">{{ $rows->count() }}</div>
                        </td>
                        <td>
                            <div class="label">Date range</div>
                            <div class="value">{{ $displayDate($dateFrom) ?: 'All' }} - {{ $displayDate($dateTo) ?: 'All' }}</div>
                        </td>
                        <td>
                            <div class="label">Report type</div>
                            <div class="value">
                                {{ str_contains($title, 'CONSUMPTION') ? 'Per vehicle totals' : 'Safari fuel entries' }}
                            </div>
                        </td>
                    </tr>
                @endif
            </table>
            <div class="section-title">{{ $title }}</div>
            <table class="report-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach ($row as $key => $value)
                                <td style="{{ $title === 'SAFARI FUEL AND AVERAGES' && $key === 'average' && $value !== null ? 'background-color: ' . ($value < 6.5 ? '#fee2e2' : ($value < 7 ? '#fef9c3' : '#dcfce7')) . '; font-weight: bold;' : '' }}" class="{{ $key === 'status' ? 'status-' . strtolower((string) $value) : '' }}">
                                    {{ $key === 'date' && $value ? $displayDate($value) : ($key === 'month' && $value ? \Carbon\Carbon::parse($value . '-01')->format('m/Y') : (is_array($value) ? ($value['plate_no'] ?? '—') : ($value === null || $value === '' ? '—' : $value))) }}
                                </td>
                            @endforeach
                    </tr>@empty<tr>
                            <td colspan="{{ count($columns) }}">No records match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
