<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Services\Security\TurnstileVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationNotificationController extends Controller
{
    public function __construct(private readonly TurnstileVerifier $turnstileVerifier) {}

    /**
     * Resend a new email verification code for unverified users.
     */
    public function store(Request $request): JsonResponse
    {
        $genericMessage = 'If the email exists and is not yet verified, a new code has been sent.';

        if (! $this->turnstileVerifier->verify((string) $request->input('turnstile_token'), $request->ip())) {
            throw ValidationException::withMessages([
                'turnstile_token' => ['Captcha verification failed. Please try again.'],
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => $genericMessage,
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => $genericMessage,
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => Carbon::now()]);

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '000000',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new EmailVerificationCodeNotification($code));

        return response()->json([
            'message' => $genericMessage,
        ]);
    }
}
