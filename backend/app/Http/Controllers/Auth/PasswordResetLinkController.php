<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\TurnstileVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    public function __construct(private readonly TurnstileVerifier $turnstileVerifier) {}

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->turnstileVerifier->verify((string) $request->input('turnstile_token'), $request->ip())) {
            throw ValidationException::withMessages([
                'turnstile_token' => ['Captcha verification failed. Please try again.'],
            ]);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Always return a generic message to avoid leaking account existence.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If the email exists, a password reset link has been sent.',
        ]);
    }
}
