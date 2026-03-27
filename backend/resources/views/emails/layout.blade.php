<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Project Linker' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f2fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f4f2fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="620" style="max-width:620px;width:100%;">
                <tr>
                    <td style="padding:0 0 14px 0;text-align:center;">
                        <div style="font-size:13px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#6f7390;">Project Linker</div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;border:1px solid #ebe9f4;border-radius:24px;overflow:hidden;box-shadow:0 10px 30px rgba(30,27,53,0.08);">
                        <div style="padding:26px 30px;background:linear-gradient(135deg,#4526c9 0%,#5b45f0 100%);color:#ffffff;">
                            <div style="font-size:12px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;opacity:0.9;">Account Email</div>
                            <div style="margin-top:8px;font-size:26px;line-height:1.25;font-weight:700;">{{ $heroTitle ?? 'Important update' }}</div>
                            @if (!empty($heroSubtitle))
                                <div style="margin-top:8px;font-size:14px;line-height:1.6;opacity:0.92;">{{ $heroSubtitle }}</div>
                            @endif
                        </div>

                        <div style="padding:28px 30px 26px 30px;">
                            {!! $slot !!}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 8px 0 8px;text-align:center;font-size:12px;line-height:1.6;color:#8b90a8;">
                        You received this email because you have an account on Project Linker.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
