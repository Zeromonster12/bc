<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationCodeController extends Controller
{
    /**
     * Verify email with a 6-digit code and allow login afterwards.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $limiterKey = sprintf('email-verification:%s|%s', $email, (string) $request->ip());
        $maxAttempts = 5;
        $lockoutSeconds = 600;

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many failed attempts. Please try again later.',
                'retry_after_seconds' => RateLimiter::availableIn($limiterKey),
            ], 429);
        }

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            RateLimiter::hit($limiterKey, $lockoutSeconds);
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 422);
        }

        $verificationCode = EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        if (! $verificationCode) {
            RateLimiter::hit($limiterKey, $lockoutSeconds);
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $matches = false;

        if (is_string($verificationCode->code_hash) && $verificationCode->code_hash !== '') {
            $matches = Hash::check((string) $validated['code'], $verificationCode->code_hash);
        } else {
            // Legacy fallback for codes created before hash-based storage.
            $matches = hash_equals((string) $verificationCode->code, (string) $validated['code']);
        }

        if (! $matches) {
            RateLimiter::hit($limiterKey, $lockoutSeconds);
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $verificationCode->update([
            'used_at' => Carbon::now(),
        ]);

        $user->forceFill([
            'email_verified_at' => Carbon::now(),
        ])->save();

        RateLimiter::clear($limiterKey);

        $nextStep = $user->role === 'company' ? 'complete_company_profile' : 'login';

        return response()->json([
            'message' => 'Email verified successfully. You can now log in.',
            'role' => $user->role,
            'company_verification_status' => $user->company_verification_status,
            'next_step' => $nextStep,
        ]);
    }
}
