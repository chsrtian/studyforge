<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyForge Login Verification</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f3f4f6; margin: 0; padding: 20px;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your StudyForge login code is {{ $otp }}. It expires in 10 minutes.</div>
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
        <tr>
            <td style="padding: 32px 40px; text-align: center; border-bottom: 1px solid #e5e7eb; background: linear-gradient(180deg, #eef2ff 0%, #ffffff 100%);">
                <h1 style="margin: 0; color: #111827; font-size: 24px; font-weight: 700; letter-spacing: 0.2px;">StudyForge</h1>
                <p style="margin: 6px 0 0; font-size: 13px; color: #6b7280;">Secure sign-in verification</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 40px;">
                <p style="margin-top: 0; margin-bottom: 20px; font-size: 16px;">Hello {{ $user->name }},</p>
                <p style="margin-top: 0; margin-bottom: 24px; font-size: 16px;">Use this one-time code to complete your login securely:</p>
                
                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; text-align: center; margin-bottom: 24px;">
                    <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 36px; font-weight: 700; letter-spacing: 4px; color: #4338ca;">{{ substr($otp, 0, 3) }} {{ substr($otp, 3, 3) }}</span>
                </div>

                <p style="margin-top: 0; margin-bottom: 8px; font-size: 14px; color: #6b7280;">This code expires in <strong>10 minutes</strong>.</p>
                <p style="margin-top: 0; margin-bottom: 0; font-size: 14px; color: #6b7280;">If you did not request this code, you can safely ignore this email.</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 24px 40px; background-color: #f9fafb; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb;">
                &copy; {{ date('Y') }} StudyForge. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
