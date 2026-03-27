<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
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
            'business_name' => ['nullable', 'string', 'max:255', 'required_if:role,company'],
            'billing_street' => ['nullable', 'string', 'max:255', 'required_if:role,company'],
            'billing_city' => ['nullable', 'string', 'max:120', 'required_if:role,company'],
            'billing_postal_code' => ['nullable', 'regex:/^\d{3}\s?\d{2}$/', 'required_if:role,company'],
            'ico' => ['nullable', 'regex:/^\d{8}$/', 'required_if:role,company'],
            'dic' => ['nullable', 'regex:/^\d{10}$/', 'required_if:role,company'],
            'ic_dph' => ['nullable', 'regex:/^SK\d{10}$/i'],
            'contact_person_full_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s]{9,20}$/', 'required_if:role,company'],
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

        if ($role === 'company') {
            $businessName = trim((string) $request->string('business_name'));
            $billingStreet = trim((string) $request->string('billing_street'));
            $billingCity = trim((string) $request->string('billing_city'));
            $billingPostalCode = preg_replace('/\s+/', ' ', trim((string) $request->string('billing_postal_code'))) ?? '';
            $ico = trim((string) $request->string('ico'));
            $dic = trim((string) $request->string('dic'));
            $icDph = strtoupper(str_replace(' ', '', trim((string) $request->string('ic_dph'))));
            $contactPersonFullNameRaw = trim((string) $request->string('contact_person_full_name'));
            $contactPersonFullName = $contactPersonFullNameRaw !== '' ? $contactPersonFullNameRaw : trim($firstName . ' ' . $lastName);
            $contactEmailRaw = strtolower(trim((string) $request->string('contact_email')));
            $contactEmail = $contactEmailRaw !== '' ? $contactEmailRaw : strtolower((string) $request->email);
            $contactPhone = preg_replace('/\s+/', ' ', trim((string) $request->string('phone'))) ?? '';

            CompanyProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'profile_data' => [
                        'name' => $businessName,
                        'business_name' => $businessName,
                        'billing_street' => $billingStreet,
                        'billing_city' => $billingCity,
                        'billing_postal_code' => $billingPostalCode,
                        'billing_country' => 'Slovakia',
                        'ico' => $ico,
                        'dic' => $dic,
                        'ic_dph' => $icDph,
                        'contact_person_full_name' => $contactPersonFullName,
                        'contact_email' => $contactEmail,
                        'contact_phone' => $contactPhone,
                        'headquarters_city' => $billingCity,
                        'headquarters_country' => 'Slovakia',
                    ],
                ]
            );
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
