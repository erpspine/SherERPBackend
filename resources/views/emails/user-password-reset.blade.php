<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Password Has Been Reset</title>
</head>

<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f4f8;padding:48px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;width:100%;">

                    <tr>
                        <td align="center"
                            style="background:linear-gradient(135deg,#7f1d1d 0%,#dc2626 100%);padding:44px 48px 36px;">
                            <h1 style="color:#ffffff;font-size:22px;font-weight:700;margin:0;letter-spacing:-0.3px;">
                                {{ config('app.name') }}
                            </h1>
                            <p style="color:#fecaca;font-size:13px;margin:8px 0 0;letter-spacing:0.3px;">
                                Password Reset Notification
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px 48px 0;">
                            <p style="font-size:17px;color:#111827;margin:0 0 6px;font-weight:600;">
                                Hello, {{ $user->name }}.
                            </p>
                            <p style="font-size:14px;color:#6b7280;margin:0 0 24px;line-height:1.7;">
                                Your account password was reset by an administrator. Use the credentials below to sign
                                in.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 48px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;overflow:hidden;">
                                <tr>
                                    <td style="padding:18px 20px 10px;">
                                        <p
                                            style="font-size:11px;font-weight:700;color:#b45309;letter-spacing:1.2px;text-transform:uppercase;margin:0 0 14px;">
                                            New Login Credentials
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 10px;font-size:14px;color:#1f2937;">
                                        <strong>Email:</strong> {{ $user->email }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 20px;font-size:14px;color:#1f2937;">
                                        <strong>Password:</strong>
                                        <span
                                            style="font-family:monospace;font-size:16px;font-weight:700;letter-spacing:2px;">
                                            {{ $plainPassword }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 48px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#fef2f2;border-left:4px solid #dc2626;border-radius:0 8px 8px 0;">
                                <tr>
                                    <td style="padding:14px 18px;">
                                        <p style="font-size:13px;color:#991b1b;margin:0;line-height:1.6;">
                                            For security, please change this temporary password immediately after
                                            logging in.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:28px 48px 0;">
                            <a href="{{ config('app.url') }}"
                                style="display:inline-block;background:linear-gradient(135deg,#7f1d1d 0%,#dc2626 100%);color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;padding:14px 40px;border-radius:8px;letter-spacing:0.3px;">
                                Sign In
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:24px 48px 36px;">
                            <p style="font-size:12px;color:#9ca3af;margin:0 0 4px;line-height:1.7;">
                                This is an automated message - please do not reply.
                            </p>
                            <p style="font-size:12px;color:#9ca3af;margin:0;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
