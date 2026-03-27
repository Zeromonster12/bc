@php
    $heroTitle = $approved ? 'Company approved' : 'Company review update';
    $heroSubtitle = $approved
        ? 'Your company account is now active.'
        : 'Your company account needs a few more updates.';
    $title = 'Project Linker - Company Account Status';

    $bodyText = $approved
        ? 'Your company account has been approved by an administrator. You can now create and manage projects on the platform.'
        : 'Your company account has been reviewed but was not approved yet. Please complete your company profile details and contact support if you believe this is a mistake.';
@endphp

@include('emails.layout', [
    'heroTitle' => $heroTitle,
    'heroSubtitle' => $heroSubtitle,
    'title' => $title,
    'slot' => '
        <div style="font-size:16px;line-height:1.7;color:#3d415a;">
            Hello '.e($name).',<br><br>
            '.e($bodyText).'
        </div>

        <div style="margin-top:18px;padding:12px 14px;border-radius:12px;'.($approved
            ? 'background:#ecfdf3;border:1px solid #b9efcf;color:#1f7a43;'
            : 'background:#fff7ed;border:1px solid #ffd7ad;color:#9a4d08;').'font-size:13px;line-height:1.6;">
            '.($approved ? 'Status: Approved' : 'Status: Not approved yet').'
        </div>
    ',
])
