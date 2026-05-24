<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Histaryo</title>
</head>
<body style="margin:0;padding:0;background:#eceff1;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#333333;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eceff1;padding:40px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border:1px solid #e0e0e0;">
                <tr>
                    <td style="padding:32px 36px 28px;">
                        <h1 style="margin:0 0 18px;font-size:22px;font-weight:400;color:#333333;">
                            Welcome to Histaryo {{ $firstName }}
                        </h1>

                        <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#555555;">
                            Below are the details of your account. Please use the link below to set your password.
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.8;color:#333333;">
                            <tr>
                                <td style="padding:2px 0;width:140px;vertical-align:top;"><strong>First Name</strong></td>
                                <td style="padding:2px 0;">{{ $firstName }}</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;vertical-align:top;"><strong>Last Name</strong></td>
                                <td style="padding:2px 0;">{{ $lastName }}</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;vertical-align:top;"><strong>Email</strong></td>
                                <td style="padding:2px 0;">{{ $email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;vertical-align:top;"><strong>Temporary Password</strong></td>
                                <td style="padding:2px 0;">{{ $plainPassword }}</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;vertical-align:top;"><strong>Role</strong></td>
                                <td style="padding:2px 0;">Curator</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;vertical-align:top;"><strong>Landmark</strong></td>
                                <td style="padding:2px 0;">{{ $landmarkLabel }}</td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#555555;">
                            <a href="{{ $changePasswordUrl }}" style="color:#7A2E1F;text-decoration:none;font-weight:600;">Set Up Password</a>
                        </p>
                    </td>
                </tr>
            </table>

            <p style="margin:20px 0 0;font-size:12px;color:#888888;text-align:center;">
                &copy; {{ date('Y') }} Histaryo. All rights reserved.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
