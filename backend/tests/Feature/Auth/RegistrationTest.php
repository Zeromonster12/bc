<?php

namespace Tests\Feature\Auth;

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
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
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
}
