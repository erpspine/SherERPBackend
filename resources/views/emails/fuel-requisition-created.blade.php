<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fuel Requisition Submitted</title>
</head>

<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6;padding:40px 14px;">
        <tr>
            <td align="center">

                <table width="620" cellpadding="0" cellspacing="0" border="0"
                    style="background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 6px 28px rgba(0,0,0,0.08);max-width:620px;width:100%;">

                    <tr>
                        <td align="center"
                            style="background:linear-gradient(135deg,#0f766e 0%,#0ea5e9 100%);padding:38px 44px 30px;">
                            <div
                                style="width:54px;height:54px;background:rgba(255,255,255,0.16);border-radius:999px;line-height:54px;text-align:center;font-size:24px;color:#ffffff;">
                                &#9981;
                            </div>
                            <h1
                                style="color:#ffffff;font-size:22px;font-weight:700;margin:14px 0 0;letter-spacing:-0.3px;">
                                Fuel Requisition Submitted
                            </h1>
                            <p style="color:#dbeafe;font-size:13px;margin:7px 0 0;letter-spacing:0.2px;">
                                {{ config('app.name') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 40px 0;">
                            <p style="font-size:16px;color:#111827;margin:0 0 8px;font-weight:600;">Hello Team,</p>
                            <p style="font-size:14px;color:#4b5563;line-height:1.65;margin:0;">
                                A new fuel requisition has been created and needs your attention.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 40px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                                <tr>
                                    <td
                                        style="padding:18px 20px 8px;font-size:11px;font-weight:700;color:#0f766e;letter-spacing:1px;text-transform:uppercase;">
                                        Requisition Details
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 20px 10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="130"
                                                    style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.7px;padding-right:10px;">
                                                    Lead
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:11px 14px;font-size:14px;color:#0f172a;font-weight:600;">
                                                    {{ $fuelRequisition->lead->booking_ref ?? '-' }}
                                                    @if (!empty($fuelRequisition->lead->client_company))
                                                        <div
                                                            style="font-size:12px;color:#64748b;font-weight:500;margin-top:3px;">
                                                            {{ $fuelRequisition->lead->client_company }}
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 20px 10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="130"
                                                    style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.7px;padding-right:10px;">
                                                    Litres Needed
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:11px 14px;font-size:16px;color:#0f766e;font-weight:700;">
                                                    {{ number_format((float) $fuelRequisition->litres, 2) }} L
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 20px 10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="130"
                                                    style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.7px;padding-right:10px;vertical-align:top;padding-top:10px;">
                                                    Reason
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:11px 14px;font-size:14px;color:#1f2937;line-height:1.6;">
                                                    {{ $fuelRequisition->reason }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 20px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="130"
                                                    style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.7px;padding-right:10px;">
                                                    Requested By
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:11px 14px;font-size:14px;color:#111827;">
                                                    {{ $requestedBy->name }}
                                                    <div style="font-size:12px;color:#64748b;margin-top:3px;">
                                                        {{ $requestedBy->email }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px 40px 34px;">
                            <p style="font-size:12px;color:#9ca3af;line-height:1.6;margin:0;">
                                This is an automated notification sent to users who opted in for system notifications.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>
