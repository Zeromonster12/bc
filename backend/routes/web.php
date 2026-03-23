<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/diagnostics/smtp-port', function (Request $request) {
    $configuredToken = (string) env('DIAGNOSTICS_TOKEN', '');
    $providedToken = (string) ($request->header('X-Diagnostics-Token') ?: $request->query('token', ''));

    if (! app()->isLocal()) {
        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }
    }

    $host = (string) $request->query('host', (string) env('MAIL_HOST', '127.0.0.1'));
    $port = (int) $request->query('port', (int) env('MAIL_PORT', 587));
    $timeout = (float) $request->query('timeout', 5);

    if ($port < 1 || $port > 65535) {
        return response()->json([
            'message' => 'Invalid port.',
        ], 422);
    }

    if ($timeout <= 0 || $timeout > 30) {
        return response()->json([
            'message' => 'Timeout must be between 0 and 30 seconds.',
        ], 422);
    }

    $start = microtime(true);
    $errno = 0;
    $errstr = '';

    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $elapsedMs = (int) round((microtime(true) - $start) * 1000);

    if (is_resource($socket)) {
        fclose($socket);
    }

    return response()->json([
        'host' => $host,
        'resolved_ip' => gethostbyname($host),
        'port' => $port,
        'timeout_seconds' => $timeout,
        'reachable' => is_resource($socket),
        'latency_ms' => $elapsedMs,
        'error_no' => is_resource($socket) ? null : $errno,
        'error_message' => is_resource($socket) ? null : ($errstr !== '' ? $errstr : 'Connection failed.'),
        'mail_env' => [
            'mailer' => env('MAIL_MAILER'),
            'host' => env('MAIL_HOST'),
            'port' => (int) env('MAIL_PORT', 0),
            'encryption' => env('MAIL_ENCRYPTION'),
            'mail_url_set' => (string) env('MAIL_URL', '') !== '',
        ],
    ]);
})->middleware('throttle:20,1');
