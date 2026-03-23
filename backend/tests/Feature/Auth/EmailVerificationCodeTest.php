<?php

namespace Tests\Feature\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_email_with_valid_code(): void
    {
        $user = User::factory()->unverified()->create();

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/auth/verify-email-code', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_can_not_verify_email_with_expired_code(): void
    {
        $user = User::factory()->unverified()->create();

        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code' => '123456',
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/auth/verify-email-code', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(422);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_can_request_new_verification_code(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->postJson('/api/auth/email/verification-notification', [
            'email' => $user->email,
        ]);

        $response->assertOk();
        Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
    }
}
