<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fuel Requisition Response</title>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
    @php
        $status = $fuelRequisition->status ?? 'Pending';
        $statusStyles = [
            'Approved' => ['bg' => '#ecfdf3', 'border' => '#86efac', 'text' => '#166534'],
            'Rejected' => ['bg' => '#fef2f2', 'border' => '#fca5a5', 'text' => '#991b1b'],
            'Pending' => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'text' => '#92400e'],
        ];
        $statusStyle = $statusStyles[$status] ?? $statusStyles['Pending'];
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;padding:40px 14px;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" border="0"
                    style="background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 28px rgba(0,0,0,0.08);max-width:620px;width:100%;">

                    <tr>
                        <td align="center"
                            style="background:linear-gradient(135deg,#0f766e 0%,#0ea5e9 100%);padding:38px 44px 30px;">
                            <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0;letter-spacing:-0.3px;">
                                Fuel Requisition Response
                            </h1>
                            <p style="color:#dbeafe;font-size:13px;margin:7px 0 0;letter-spacing:0.2px;">
                                {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 40px 0;">
                            <p style="font-size:16px;color:#111827;margin:0 0 8px;font-weight:600;">Hello
                                {{ $fuelRequisition->requester->name ?? 'User' }},</p>
                            <p style="font-size:14px;color:#4b5563;line-height:1.65;margin:0;">
                                Your fuel requisition has been reviewed.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 40px 0;">
                            <div
                                style="display:inline-block;padding:8px 12px;border-radius:999px;border:1px solid {{ $statusStyle['border'] }};background:{{ $statusStyle['bg'] }};color:{{ $statusStyle['text'] }};font-size:12px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;">
                                Status: {{ $status }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 40px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                                <tr>
                                    <td
                                        style="padding:16px 18px 10px;font-size:11px;font-weight:700;color:#0f766e;letter-spacing:1px;text-transform:uppercase;">
                                        Requisition Details
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 8px;font-size:14px;color:#0f172a;">
                                        <strong>Lead:</strong>
                                        {{ $fuelRequisition->lead->booking_ref ?? '#' . $fuelRequisition->lead_id }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 8px;font-size:14px;color:#0f172a;">
                                        <strong>Litres Requested:</strong>
                                        {{ number_format((float) $fuelRequisition->litres, 2) }} L
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 8px;font-size:14px;color:#0f172a;">
                                        <strong>Reason:</strong> {{ $fuelRequisition->reason }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 8px;font-size:14px;color:#0f172a;">
                                        <strong>Responded By:</strong> {{ $responder->name }} ({{ $responder->email }})
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 18px 16px;font-size:14px;color:#0f172a;">
                                        <strong>Responded At:</strong>
                                        {{ optional($fuelRequisition->responded_at)->format('Y-m-d H:i') ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if (!empty($fuelRequisition->response_note))
                        <tr>
                            <td style="padding:16px 40px 0;">
                                <div
                                    style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;">
                                    Response Note
                                </div>
                                <div
                                    style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:12px 14px;font-size:14px;color:#1f2937;line-height:1.65;">
                                    {{ $fuelRequisition->response_note }}
                                </div>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:22px 40px 30px;">
                            <p style="font-size:12px;color:#9ca3af;line-height:1.6;margin:0;">
                                This is an automated notification from {{ config('app.name') }}.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
