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
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly TurnstileVerifier $turnstileVerifier) {}

    /**
     * Handle an incoming registration request.
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,company'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'role' => $request->string('role'),
        ]);

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
            'message' => 'Verification code sent to your email.',
            'email' => $user->email,
        ], 201);
    }
}
