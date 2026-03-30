<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Account Has Been Created</title>
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
                            style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);padding:44px 48px 36px;">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center"
                                        style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;text-align:center;vertical-align:middle;">
                                        <span style="font-size:26px;line-height:56px;">&#128100;</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:16px;">
                                        <h1
                                            style="color:#ffffff;font-size:22px;font-weight:700;margin:0;letter-spacing:-0.3px;">
                                            {{ config('app.name') }}
                                        </h1>
                                        <p style="color:#bfdbfe;font-size:13px;margin:6px 0 0;letter-spacing:0.3px;">
                                            User Account Management System
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
                                Hello, {{ $user->name }}! &#127881;
                            </p>
                            <p style="font-size:14px;color:#6b7280;margin:0 0 32px;line-height:1.7;">
                                Welcome aboard! Your account has been successfully created on
                                <strong style="color:#374151;">{{ config('app.name') }}</strong>.
                                Below are your login credentials. Please keep them safe and secure.
                            </p>
                        </td>
                    </tr>

                    <!-- Credentials Box -->
                    <tr>
                        <td style="padding:0 48px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:10px;overflow:hidden;">
                                <tr>
                                    <td style="padding:20px 24px 12px;">
                                        <p
                                            style="font-size:11px;font-weight:700;color:#1d4ed8;letter-spacing:1.2px;
                                       text-transform:uppercase;margin:0 0 18px;">
                                            &#128273;&nbsp; Your Login Credentials
                                        </p>
                                    </td>
                                </tr>

                                <!-- Row: Email -->
                                <tr>
                                    <td style="padding:0 24px 12px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="90" valign="middle"
                                                    style="font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Email
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #dbeafe;border-radius:7px;
                                                padding:11px 15px;font-size:14px;color:#1e40af;font-weight:600;">
                                                    {{ $user->email }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Row: Password -->
                                <tr>
                                    <td style="padding:0 24px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="90" valign="middle"
                                                    style="font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;
                                                letter-spacing:.7px;padding-right:12px;">
                                                    Password
                                                </td>
                                                <td
                                                    style="background:#ffffff;border:1px solid #dbeafe;border-radius:7px;
                                                padding:11px 15px;font-family:monospace;font-size:16px;
                                                color:#111827;font-weight:700;letter-spacing:3px;">
                                                    {{ $plainPassword }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Role & Status info -->
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
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 3px;text-transform:uppercase;letter-spacing:.7px;">
                                                        Role</p>
                                                    <p style="font-size:14px;font-weight:600;color:#374151;margin:0;">
                                                        {{ $user->role ?? '—' }}</p>
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
                                                        style="font-size:11px;color:#9ca3af;margin:0 0 3px;text-transform:uppercase;letter-spacing:.7px;">
                                                        Status</p>
                                                    <p
                                                        style="font-size:14px;font-weight:600;
                                                   color:{{ $user->status === 'Active' ? '#059669' : '#dc2626' }};margin:0;">
                                                        {{ $user->status }}
                                                    </p>
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
                        <td align="center" style="padding:32px 48px 0;">
                            <a href="{{ config('app.url') }}"
                                style="display:inline-block;background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);
                           color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;
                           padding:14px 40px;border-radius:8px;letter-spacing:0.3px;">
                                &#128274;&nbsp; Login to Your Account
                            </a>
                        </td>
                    </tr>

                    <!-- Security Notice -->
                    <tr>
                        <td style="padding:28px 48px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#fff7ed;border-left:4px solid #f97316;border-radius:0 8px 8px 0;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p style="font-size:13px;color:#9a3412;margin:0;line-height:1.6;">
                                            <strong>&#9888; Security Notice:</strong>
                                            Please change your password immediately after your first login.
                                            Do not share your credentials with anyone.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:36px 48px 0;">
                            <hr style="border:none;border-top:1px solid #f3f4f6;margin:0;" />
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:24px 48px 36px;">
                            <p style="font-size:12px;color:#9ca3af;margin:0 0 4px;line-height:1.7;">
                                This is an automated message — please do not reply.
                            </p>
                            <p style="font-size:12px;color:#9ca3af;margin:0;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Card -->

            </td>
        </tr>
    </table>

</body>

</html>
