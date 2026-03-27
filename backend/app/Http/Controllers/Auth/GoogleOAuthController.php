<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\LoginMetadataLogger;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleOAuthController extends Controller
{
    public function __construct(private readonly LoginMetadataLogger $metadataLogger) {}

    /**
     * Return Google OAuth redirect URL for SPA.
     */
    public function redirect(Request $request): JsonResponse
    {
        $provider = $this->googleProvider();

        $url = $provider
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Handle Google callback and issue API token.
     */
    public function callback(Request $request): JsonResponse
    {
        if (! $request->filled('code')) {
            return response()->json([
                'message' => 'Google callback is missing the authorization code.',
            ], 422);
        }

        try {
            $provider = $this->googleProvider();
            $googleUser = $provider->stateless()->user();
        } catch (Throwable $e) {
            $this->metadataLogger->log($request, null, null, false, 'oauth_google_error');

            Log::warning('Google OAuth callback failed.', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            $isInvalidGrant = str_contains(mb_strtolower($e->getMessage()), 'invalid_grant');
            $message = $isInvalidGrant
                ? 'Google authorization code is invalid or already used. Please try signing in again.'
                : 'Google authentication failed.';

            if (config('app.debug')) {
                $message .= ' ' . $e->getMessage();
            }

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $providerUserId = (string) $googleUser->getId();
        $email = (string) $googleUser->getEmail();

        if ($email === '') {
            $this->metadataLogger->log($request, null, null, false, 'oauth_google_missing_email');

            return response()->json([
                'message' => 'Google account does not expose an email address.',
            ], 422);
        }

        $socialAccount = SocialAccount::query()
            ->where('provider', 'google')
            ->where('provider_user_id', $providerUserId)
            ->first();

        $user = $socialAccount?->user;

        if (! $user) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $googleUser->getName() ?: 'Google User',
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'student',
                    'email_verified_at' => now(),
                ]
            );

            SocialAccount::query()->updateOrCreate(
                [
                    'provider' => 'google',
                    'provider_user_id' => $providerUserId,
                ],
                [
                    'user_id' => $user->id,
                    'avatar_url' => $googleUser->getAvatar(),
                    'profile_data' => [
                        'nickname' => $googleUser->getNickname(),
                    ],
                ]
            );
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->metadataLogger->log($request, $user, $email, true, 'oauth_google_success');

        return response()->json([
            'token' => $token,
            'data' => $user,
        ]);
    }

    private function googleProvider(): AbstractProvider
    {
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('google');

        $provider->setHttpClient(new Client([
            'verify' => $this->shouldVerifyTlsForGoogle(),
        ]));

        return $provider;
    }

    private function shouldVerifyTlsForGoogle(): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return true;
        }

        return (bool) config('services.google.verify_ssl', true);
    }
}
