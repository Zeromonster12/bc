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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,company'],
        ]);

        $role = (string) $request->string('role');
        $firstName = trim((string) $request->string('first_name'));
        $lastName = trim((string) $request->string('last_name'));
        $companyStatus = $role === 'company'
            ? User::COMPANY_STATUS_PENDING
            : User::COMPANY_STATUS_APPROVED;

        $user = User::create([
            'name' => trim($firstName . ' ' . $lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'role' => $role,
            'company_verification_status' => $companyStatus,
            'company_verified_at' => $companyStatus === User::COMPANY_STATUS_APPROVED ? now() : null,
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

        $notificationSent = true;
        try {
            $user->notify(new EmailVerificationCodeNotification($code));
        } catch (\Throwable $e) {
            $notificationSent = false;
            report($e);
        }

        $message = $notificationSent
            ? 'Verification code sent to your email.'
            : 'Account created, but verification code could not be sent. Please use resend code on the verification screen.';

        return response()->json([
            'message' => $message,
            'email' => $user->email,
            'verification_email_sent' => $notificationSent,
        ], 201);
    }
}
