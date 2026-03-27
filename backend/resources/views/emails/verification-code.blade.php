@php
    $heroTitle = 'Verify your email';
    $heroSubtitle = 'Use the verification code below to finish your registration.';
    $title = 'Project Linker - Verification Code';
@endphp

@include('emails.layout', [
    'heroTitle' => $heroTitle,
    'heroSubtitle' => $heroSubtitle,
    'title' => $title,
    'slot' => '
        <div style="font-size:16px;line-height:1.7;color:#3d415a;">
            Hello '.e($name).',<br><br>
            Enter this code in the app to verify your email address:
        </div>

        <div style="margin:18px 0 16px 0;padding:14px 18px;border-radius:14px;background:#f2effb;border:1px solid #e4def8;text-align:center;">
            <span style="font-size:32px;line-height:1;font-weight:800;letter-spacing:0.24em;color:#2f2560;">'.e($code).'</span>
        </div>

        <div style="font-size:14px;line-height:1.7;color:#5f6480;">
            This code expires in 10 minutes.<br>
            If you did not request this, you can safely ignore this email.
        </div>
    ',
])
