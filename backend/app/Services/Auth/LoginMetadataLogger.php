<?php

namespace App\Services\Auth;

use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Http\Request;

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
        $country = strtoupper((string) $request->header('CF-IPCountry', ''));

        if ($country === '' || strlen($country) !== 2) {
            return null;
        }

        return $country;
    }
}
