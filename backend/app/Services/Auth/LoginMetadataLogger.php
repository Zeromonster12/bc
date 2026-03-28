<?php

namespace App\Services\Auth;

use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class LoginMetadataLogger
{
    public function log(Request $request, ?User $user, ?string $email, bool $success, string $reasonCode): void
    {
        $appKey = (string) config('app.key');
        $normalizedEmail = $email ? mb_strtolower(trim($email)) : null;
        $userAgent = $request->userAgent();
        $deviceSource = ($userAgent ?? '') . '|' . ((string) $request->header('Accept-Language'));

        LoginEvent::query()->create([
            'user_id' => $user?->id,
            'email_submitted_hash' => $normalizedEmail ? hash('sha256', $normalizedEmail . '|' . $appKey) : null,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip() . '|' . $appKey) : null,
            'user_agent' => $userAgent,
            'device_hash' => hash('sha256', $deviceSource . '|' . $appKey),
            'country_code' => $this->countryCode($request),
            'success' => $success,
            'reason_code' => $reasonCode,
            'occurred_at' => now(),
        ]);
    }

    private function countryCode(Request $request): ?string
    {
        $candidateHeaders = [
            'X-Country-Code',
            'X-Geo-Country',
            'X-AppEngine-Country',
            'X-Vercel-IP-Country',
            'X-Azure-ClientIP-Country',
        ];

        foreach ($candidateHeaders as $header) {
            $country = strtoupper(trim((string) $request->header($header, '')));

            if ($country === '' || strlen($country) !== 2) {
                continue;
            }

            if (!ctype_alpha($country)) {
                continue;
            }

            if (in_array($country, ['XX', 'T1'], true)) {
                continue;
            }

            return $country;
        }

        return $this->countryCodeFromGeoIp($request->ip());
    }

    private function countryCodeFromGeoIp(?string $ip): ?string
    {
        if (!config('services.geoip.enabled', false)) {
            return null;
        }

        if (!$this->isPublicIp($ip)) {
            return null;
        }

        $lookupUrlTemplate = (string) config('services.geoip.lookup_url', '');
        if ($lookupUrlTemplate === '') {
            return null;
        }

        $lookupUrl = str_replace('{ip}', urlencode((string) $ip), $lookupUrlTemplate);
        $timeoutSeconds = (float) config('services.geoip.timeout_seconds', 1.0);

        try {
            $response = Http::timeout(max(0.25, $timeoutSeconds))->acceptJson()->get($lookupUrl);
            if (!$response->successful()) {
                return null;
            }

            $payload = $response->json();
            if (!is_array($payload)) {
                return null;
            }

            $status = strtoupper((string) ($payload['status'] ?? 'SUCCESS'));
            if ($status !== 'SUCCESS') {
                return null;
            }

            $countryCode = strtoupper(trim((string) ($payload['countryCode'] ?? '')));
            if (strlen($countryCode) !== 2 || !ctype_alpha($countryCode)) {
                return null;
            }

            if (in_array($countryCode, ['XX', 'T1'], true)) {
                return null;
            }

            return $countryCode;
        } catch (Throwable) {
            return null;
        }
    }

    private function isPublicIp(?string $ip): bool
    {
        if (!is_string($ip) || trim($ip) === '') {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
