<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Auth\LoginMetadataLogger;
use App\Services\Security\TurnstileVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly LoginMetadataLogger $metadataLogger,
        private readonly TurnstileVerifier $turnstileVerifier,
    ) {}

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        if (! $this->turnstileVerifier->verify((string) $request->input('turnstile_token'), $request->ip())) {
            throw ValidationException::withMessages([
                'turnstile_token' => ['Captcha verification failed. Please try again.'],
            ]);
        }

        try {
            /** @var User $user */
            $user = $request->authenticate();
        } catch (ValidationException $e) {
            $this->metadataLogger->log(
                $request,
                null,
                (string) $request->input('email'),
                false,
                'invalid_credentials'
            );

            throw $e;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->metadataLogger->log(
                $request,
                $user,
                (string) $request->input('email'),
                false,
                'user_not_verified'
            );

            return response()->json([
                'message' => 'Please verify your email with the code we sent before logging in.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->metadataLogger->log(
            $request,
            $user,
            (string) $request->input('email'),
            true,
            'password_login_success'
        );

        return response()->json([
            'token' => $token,
            'data' => $user,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        if ($request->bearerToken()) {
            PersonalAccessToken::findToken($request->bearerToken())?->delete();
        }

        $request->user()->tokens()->delete();

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
