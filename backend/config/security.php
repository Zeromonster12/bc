<?php

return [
    'cv_upload_max_kb' => (int) env('CV_UPLOAD_MAX_KB', 5120),
    'cv_antivirus_enabled' => (bool) env('CV_ANTIVIRUS_ENABLED', false),
    'cv_antivirus_required' => (bool) env('CV_ANTIVIRUS_REQUIRED', false),
    'cv_antivirus_command' => env('CV_ANTIVIRUS_COMMAND', 'clamscan --no-summary'),
];
