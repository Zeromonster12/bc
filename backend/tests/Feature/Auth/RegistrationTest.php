<?php

namespace Tests\Feature\Auth;

use App\Models\CompanyProfile;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
        ]);

        $response->assertCreated()->assertJsonStructure([
            'message',
            'email',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
    }

    public function test_company_registration_stores_company_profile_data(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Klara',
            'last_name' => 'Majerova',
            'email' => 'klara@firmatest.sk',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'company',
            'business_name' => 'Firma Test s.r.o.',
            'billing_street' => 'Nivy 10',
            'billing_city' => 'Bratislava',
            'billing_postal_code' => '821 01',
            'ico' => '12345678',
            'dic' => '1234567890',
            'ic_dph' => 'sk1234567890',
            'contact_person_full_name' => 'Klara Majerova',
            'contact_email' => 'kontakt@firmatest.sk',
            'phone' => '+421 900 123 456',
        ]);

        $response->assertCreated();

        $user = User::query()->where('email', 'klara@firmatest.sk')->firstOrFail();
        $this->assertSame(User::COMPANY_STATUS_PENDING, $user->company_verification_status);

        $profile = CompanyProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Firma Test s.r.o.', $profile->profile_data['business_name'] ?? null);
        $this->assertSame('kontakt@firmatest.sk', $profile->profile_data['contact_email'] ?? null);
        $this->assertSame('SK1234567890', $profile->profile_data['ic_dph'] ?? null);
    }
}
