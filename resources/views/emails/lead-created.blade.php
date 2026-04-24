<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New Lead Created</title>
</head>

<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f4f8;padding:48px 16px;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;width:100%;">

                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);padding:44px 48px 36px;">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center"
                                        style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;text-align:center;vertical-align:middle;">
                                        <span style="font-size:26px;line-height:56px;">&#127828;</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:16px;">
                                        <h1
                                            style="color:#ffffff;font-size:22px;font-weight:700;margin:0;letter-spacing:-0.3px;">
                                            New Lead Created
                                        </h1>
                                        <p style="color:#d1fae5;font-size:13px;margin:6px 0 0;letter-spacing:0.3px;">
                                            {{ config('app.name') }} Lead Management System
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding:40px 48px 0;">
                            <p style="font-size:17px;color:#111827;margin:0 0 6px;font-weight:600;">
                                Hello! &#128075;
                            </p>
                            <p style="font-size:14px;color:#6b7280;margin:0 0 32px;line-height:1.7;">
                                A new lead has been created and assigned for your attention. Below are the details of
                                this exciting opportunity.
                            </p>
                        </td>
                    </tr>

                    <!-- Lead Info Box -->
                    <tr>
                        <td style="padding:0 48px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;overflow:hidden;">
                                <tr>
                                    <td style="padding:20px 24px 12px;">
                                        <p
                                            style="font-size:11px;font-weight:700;color:#047857;letter-spacing:1.2px;
                                       text-transform:uppercase;margin:0 0 18px;">
                                            &#128204;&nbsp; Lead Details
                                        </p>
                                    </td>
                                </tr>

                                <!-- Row: Booking Reference -->
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="100" valign="middle"
                                                    style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Booking Ref
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #bbf7d0;border-radius:7px;
                                                padding:11px 15px;font-size:14px;color:#047857;font-weight:600;font-family:monospace;">
                                                    {{ $lead->booking_ref }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Client Company -->
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="100" valign="middle"
                                                    style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Company
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #bbf7d0;border-radius:7px;
                                                padding:11px 15px;font-size:14px;color:#111827;font-weight:600;">
                                                    {{ $lead->client_company }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Agent Contact -->
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="100" valign="middle"
                                                    style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Contact
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #bbf7d0;border-radius:7px;
                                                padding:11px 15px;font-size:14px;color:#111827;">
                                                    {{ $lead->agent_contact }}<br />
                                                    <span
                                                        style="color:#6b7280;font-size:12px;">{{ $lead->agent_email }}<br />{{ $lead->agent_phone }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Country -->
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="100" valign="middle"
                                                    style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Country
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #bbf7d0;border-radius:7px;
                                                padding:11px 15px;font-size:14px;color:#111827;">
                                                    {{ $lead->client_country }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Date Range -->
                                <tr>
                                    <td style="padding:0 24px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="100" valign="middle"
                                                    style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Dates
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #bbf7d0;border-radius:7px;
                                                padding:11px 15px;font-size:14px;color:#111827;">
                                                    {{ $lead->start_date->format('M d, Y') }} —
                                                    {{ $lead->end_date->format('M d, Y') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Trip Details Grid -->
                    <tr>
                        <td style="padding:24px 48px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="33.33%" style="padding-right:8px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                            <tr>
                                                <td style="padding:16px 14px;">
                                                    <p
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 6px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">
                                                        Adults</p>
                                                    <p style="font-size:20px;font-weight:700;color:#047857;margin:0;">
                                                        {{ $lead->pax_adults }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="33.33%" style="padding:0 4px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                            <tr>
                                                <td style="padding:16px 14px;">
                                                    <p
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 6px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">
                                                        Children</p>
                                                    <p style="font-size:20px;font-weight:700;color:#047857;margin:0;">
                                                        {{ $lead->pax_children }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="33.33%" style="padding-left:8px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                            <tr>
                                                <td style="padding:16px 14px;">
                                                    <p
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 6px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">
                                                        Vehicles</p>
                                                    <p style="font-size:20px;font-weight:700;color:#047857;margin:0;">
                                                        {{ $lead->no_of_vehicles }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Route Parks -->
                    <tr>
                        <td style="padding:20px 48px 0;">
                            <p
                                style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.7px;margin:0 0 8px;">
                                &#127964;&nbsp; Parks & Route</p>
                            <div
                                style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;font-size:13px;color:#374151;line-height:1.6;">
                                {{ $lead->route_parks }}
                            </div>
                        </td>
                    </tr>

                    <!-- Special Requirements -->
                    @if ($lead->special_requirements)
                        <tr>
                            <td style="padding:20px 48px 0;">
                                <p
                                    style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.7px;margin:0 0 8px;">
                                    &#128221;&nbsp; Special Requirements</p>
                                <div
                                    style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;font-size:13px;color:#92400e;line-height:1.6;">
                                    {{ $lead->special_requirements }}
                                </div>
                            </td>
                        </tr>
                    @endif

                    <!-- Status & Created By -->
                    <tr>
                        <td style="padding:20px 48px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="50%" style="padding-right:8px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                            <tr>
                                                <td style="padding:12px 16px;">
                                                    <p
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 3px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">
                                                        Status</p>
                                                    <p
                                                        style="font-size:13px;font-weight:600;color:
                                                   @switch($lead->booking_status)
                                                       @case('Pending') #f59e0b @break
                                                       @case('Confirmed') #10b981 @break
                                                       @case('Cancelled') #ef4444 @break
                                                       @case('Completed') #3b82f6 @break
                                                       @case('Quotation Sent') #8b5cf6 @break
                                                       @case('PI Sent') #06b6d4 @break
                                                       @default #6b7280
                                                   @endswitch
                                                ;margin:0;">
                                                        {{ $lead->booking_status }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" style="padding-left:8px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                            <tr>
                                                <td style="padding:12px 16px;">
                                                    <p
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 3px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;">
                                                        Created By</p>
                                                    <p style="font-size:13px;font-weight:600;color:#374151;margin:0;">
                                                        {{ $createdBy?->name ?? 'Website API' }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CTA Button -->
                    <tr>
                        <td style="padding:32px 48px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/leads') }}"
                                            style="display:inline-block;background:linear-gradient(135deg,#059669 0%,#10b981 100%);color:#ffffff;padding:12px 32px;border-radius:8px;font-weight:600;text-decoration:none;font-size:14px;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(5, 150, 105, 0.3);">
                                            View Lead in Portal
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="padding:24px 48px;border-top:1px solid #f3f4f6;background:#fafbfc;text-align:center;">
                            <p style="font-size:11px;color:#9ca3af;margin:0;line-height:1.6;">
                                This is an automated notification from {{ config('app.name') }} Lead Management
                                System.<br />
                                If you have any questions, please contact your administrator.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
