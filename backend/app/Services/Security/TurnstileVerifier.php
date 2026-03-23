<?php

namespace App\Services\Security;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    public function verify(?string $token, ?string $ipAddress = null): bool
    {
        if (! config('services.turnstile.enabled')) {
            return true;
        }

        $secret = (string) config('services.turnstile.secret_key');
        $verifyUrl = (string) config('services.turnstile.siteverify_url');
        $verifySsl = (bool) config('services.turnstile.verify_ssl', true);

        if ($secret === '' || ! $token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->withOptions(['verify' => $verifySsl])
                ->post($verifyUrl, [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);
        } catch (ConnectionException) {
            return false;
        }

        if (! $response->ok()) {
            return false;
        }

        return (bool) $response->json('success');
    }
}
