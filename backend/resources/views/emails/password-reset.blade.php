@php
    $heroTitle = 'Reset your password';
    $heroSubtitle = 'Use the secure link below to set a new password.';
    $title = 'Project Linker - Reset Password';
@endphp

@include('emails.layout', [
    'heroTitle' => $heroTitle,
    'heroSubtitle' => $heroSubtitle,
    'title' => $title,
    'slot' => '
        <div style="font-size:16px;line-height:1.7;color:#3d415a;">
            Hello '.e($name).',<br><br>
            We received a request to reset your password.
        </div>

        <div style="margin:20px 0 18px 0;text-align:center;">
            <a href="'.e($resetUrl).'" style="display:inline-block;padding:12px 24px;border-radius:999px;background:linear-gradient(135deg,#4526c9 0%,#5b45f0 100%);color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;box-shadow:0 8px 20px rgba(77,55,197,0.35);">
                Reset password
            </a>
        </div>

        <div style="font-size:13px;line-height:1.7;color:#5f6480;word-break:break-all;">
            If the button does not work, copy and paste this URL into your browser:<br>
            <a href="'.e($resetUrl).'" style="color:#4b35cb;text-decoration:underline;">'.e($resetUrl).'</a><br><br>
            This link expires automatically. If you did not request this, no further action is required.
        </div>
    ',
])
