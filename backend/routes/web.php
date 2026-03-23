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
    $portsCsv = (string) $request->query('ports', (string) $port);
    $probeTls = filter_var($request->query('probe_tls', true), FILTER_VALIDATE_BOOLEAN);

    if ($timeout <= 0 || $timeout > 30) {
        return response()->json([
            'message' => 'Timeout must be between 0 and 30 seconds.',
        ], 422);
    }

    $ports = array_values(array_unique(array_filter(array_map(
        static fn(string $item): int => (int) trim($item),
        explode(',', $portsCsv)
    ), static fn(int $p): bool => $p >= 1 && $p <= 65535)));

    if ($ports === []) {
        return response()->json([
            'message' => 'No valid ports were provided.',
        ], 422);
    }

    $dnsA = @dns_get_record($host, DNS_A) ?: [];
    $dnsAaaa = @dns_get_record($host, DNS_AAAA) ?: [];

    $connect = static function (string $transport, string $host, int $port, float $timeout, bool $readBanner = false): array {
        $errno = 0;
        $errstr = '';
        $target = sprintf('%s://%s:%d', $transport, $host, $port);
        $start = microtime(true);

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        $elapsedMs = (int) round((microtime(true) - $start) * 1000);
        $reachable = is_resource($socket);
        $banner = null;

        if ($reachable && $readBanner) {
            stream_set_timeout($socket, 2);
            $line = @fgets($socket, 512);
            $banner = is_string($line) ? trim($line) : null;
        }

        if ($reachable) {
            fclose($socket);
        }

        return [
            'target' => $target,
            'reachable' => $reachable,
            'latency_ms' => $elapsedMs,
            'error_no' => $reachable ? null : $errno,
            'error_message' => $reachable ? null : ($errstr !== '' ? $errstr : 'Connection failed.'),
            'banner' => $banner,
        ];
    };

    $checks = [];
    foreach ($ports as $testPort) {
        $entry = [
            'port' => $testPort,
            'tcp' => $connect('tcp', $host, $testPort, $timeout, true),
        ];

        if ($probeTls) {
            $entry['implicit_tls'] = $connect('ssl', $host, $testPort, $timeout, false);
        }

        $checks[] = $entry;
    }

    $primary = collect($checks)->firstWhere('port', $port) ?? $checks[0];

    return response()->json([
        'host' => $host,
        'resolved_ip' => gethostbyname($host),
        'dns' => [
            'a' => array_values(array_filter(array_map(
                static fn(array $record): ?string => $record['ip'] ?? null,
                $dnsA
            ))),
            'aaaa' => array_values(array_filter(array_map(
                static fn(array $record): ?string => $record['ipv6'] ?? null,
                $dnsAaaa
            ))),
        ],
        'port' => $port,
        'ports_tested' => $ports,
        'timeout_seconds' => $timeout,
        'reachable' => (bool) data_get($primary, 'tcp.reachable', false),
        'latency_ms' => data_get($primary, 'tcp.latency_ms'),
        'error_no' => data_get($primary, 'tcp.error_no'),
        'error_message' => data_get($primary, 'tcp.error_message'),
        'checks' => $checks,
        'mail_env' => [
            'mailer' => env('MAIL_MAILER'),
            'host' => env('MAIL_HOST'),
            'port' => (int) env('MAIL_PORT', 0),
            'encryption' => env('MAIL_ENCRYPTION'),
            'mail_url_set' => (string) env('MAIL_URL', '') !== '',
        ],
    ]);
})->middleware('throttle:20,1');
