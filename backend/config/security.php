<?php

return [
    'cv_upload_max_kb' => (int) env('CV_UPLOAD_MAX_KB', 5120),
    'cv_antivirus_enabled' => (bool) env('CV_ANTIVIRUS_ENABLED', false),
    'cv_antivirus_required' => (bool) env('CV_ANTIVIRUS_REQUIRED', false),
    'cv_antivirus_driver' => env('CV_ANTIVIRUS_DRIVER', 'command'),
    'cv_antivirus_clamd_host' => env('CV_ANTIVIRUS_CLAMD_HOST', 'clamav'),
    'cv_antivirus_clamd_port' => (int) env('CV_ANTIVIRUS_CLAMD_PORT', 3310),
    'cv_antivirus_clamd_timeout' => (float) env('CV_ANTIVIRUS_CLAMD_TIMEOUT', 10),
    'cv_antivirus_command' => env('CV_ANTIVIRUS_COMMAND', 'clamscan --no-summary'),
];
