<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GitHubConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.github.client_id' => 'test-client-id',
            'services.github.client_secret' => 'test-client-secret',
            'services.github.redirect' => 'http://localhost:5173/profile/student/github/callback',
        ]);
    }

    public function test_student_can_link_github_account(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        Sanctum::actingAs($user);

        $provider = \Mockery::mock(AbstractProvider::class);
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = 'github-123';
        $socialiteUser->nickname = 'octocat';
        $socialiteUser->name = 'Octo Cat';
        $socialiteUser->email = 'octocat@example.com';
        $socialiteUser->avatar = 'https://avatars.example.com/octocat';
        $socialiteUser->user = ['html_url' => 'https://github.com/octocat'];

        Socialite::shouldReceive('driver')
            ->once()
            ->with('github')
            ->andReturn($provider);

        $provider->shouldReceive('scopes')->once()->with(['read:user', 'user:email'])->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->once()->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        $response = $this->getJson('/api/auth/github/connect/callback?code=fake-code');

        $response->assertOk()->assertJson([
            'connected' => true,
            'data' => [
                'username' => 'octocat',
                'profile_url' => 'https://github.com/octocat',
            ],
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_user_id' => 'github-123',
        ]);
    }

    public function test_student_cannot_link_github_account_already_linked_to_another_user(): void
    {
        $currentUser = User::factory()->create(['role' => 'student']);
        $otherUser = User::factory()->create(['role' => 'student']);

        SocialAccount::query()->create([
            'user_id' => $otherUser->id,
            'provider' => 'github',
            'provider_user_id' => 'github-123',
            'avatar_url' => null,
            'profile_data' => null,
        ]);

        Sanctum::actingAs($currentUser);

        $provider = \Mockery::mock(AbstractProvider::class);
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = 'github-123';

        Socialite::shouldReceive('driver')
            ->once()
            ->with('github')
            ->andReturn($provider);

        $provider->shouldReceive('scopes')->once()->with(['read:user', 'user:email'])->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->once()->andReturnSelf();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialiteUser);

        $response = $this->getJson('/api/auth/github/connect/callback?code=fake-code');

        $response->assertStatus(409)->assertJson([
            'message' => 'This GitHub account is already connected to another user.',
        ]);
    }

    public function test_student_can_disconnect_github_account(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $account = SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_user_id' => 'github-123',
            'avatar_url' => null,
            'profile_data' => [
                'nickname' => 'octocat',
                'html_url' => 'https://github.com/octocat',
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/auth/github/connect');

        $response->assertOk()->assertJson([
            'connected' => false,
            'data' => null,
        ]);

        $this->assertDatabaseMissing('social_accounts', [
            'id' => $account->id,
        ]);
    }
}
