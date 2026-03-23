<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Throwable;

class GitHubConnectionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'GitHub connection is available only for student accounts.',
            ], 403);
        }

        $account = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'github')
            ->first();

        if (! $account) {
            return response()->json([
                'connected' => false,
                'data' => null,
            ]);
        }

        $profileData = is_array($account->profile_data) ? $account->profile_data : [];

        return response()->json([
            'connected' => true,
            'data' => [
                'username' => (string) ($profileData['nickname'] ?? ''),
                'profile_url' => (string) ($profileData['html_url'] ?? ''),
                'avatar_url' => $account->avatar_url,
                'connected_at' => $account->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function redirect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'GitHub connection is available only for student accounts.',
            ], 403);
        }

        $configError = $this->githubConfigError();
        if ($configError) {
            return $configError;
        }

        $provider = $this->githubProvider();

        $url = $provider
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'GitHub connection is available only for student accounts.',
            ], 403);
        }

        $configError = $this->githubConfigError();
        if ($configError) {
            return $configError;
        }

        if (! $request->filled('code')) {
            return response()->json([
                'message' => 'GitHub callback is missing the authorization code.',
            ], 422);
        }

        try {
            $provider = $this->githubProvider();
            $githubUser = $provider->stateless()->user();
        } catch (Throwable $e) {
            $message = 'GitHub authentication failed.';

            if (config('app.debug')) {
                $message .= ' ' . $e->getMessage();
            }

            return response()->json([
                'message' => $message,
            ], 422);
        }

        $providerUserId = (string) $githubUser->getId();

        if ($providerUserId === '') {
            return response()->json([
                'message' => 'GitHub account ID is missing in OAuth response.',
            ], 422);
        }

        $existing = SocialAccount::query()
            ->where('provider', 'github')
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            return response()->json([
                'message' => 'This GitHub account is already connected to another user.',
            ], 409);
        }

        $socialAccount = SocialAccount::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => 'github',
            ],
            [
                'provider_user_id' => $providerUserId,
                'avatar_url' => $githubUser->getAvatar(),
                'profile_data' => [
                    'nickname' => $githubUser->getNickname(),
                    'name' => $githubUser->getName(),
                    'email' => $githubUser->getEmail(),
                    'html_url' => $githubUser->user['html_url'] ?? null,
                ],
            ]
        );

        $profileData = is_array($socialAccount->profile_data) ? $socialAccount->profile_data : [];

        return response()->json([
            'connected' => true,
            'data' => [
                'username' => (string) ($profileData['nickname'] ?? ''),
                'profile_url' => (string) ($profileData['html_url'] ?? ''),
                'avatar_url' => $socialAccount->avatar_url,
                'connected_at' => $socialAccount->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'GitHub connection is available only for student accounts.',
            ], 403);
        }

        SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'github')
            ->delete();

        return response()->json([
            'connected' => false,
            'data' => null,
        ]);
    }

    public function insights(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'GitHub connection is available only for student accounts.',
            ], 403);
        }

        $account = SocialAccount::query()
            ->where('user_id', $user->id)
            ->where('provider', 'github')
            ->first();

        if (! $account) {
            return response()->json([
                'connected' => false,
                'data' => [
                    'repositories' => [],
                    'recent_commits' => [],
                ],
            ]);
        }

        $profileData = is_array($account->profile_data) ? $account->profile_data : [];
        $username = trim((string) ($profileData['nickname'] ?? ''));

        if ($username === '') {
            return response()->json([
                'connected' => true,
                'data' => [
                    'repositories' => [],
                    'recent_commits' => [],
                ],
            ]);
        }

        $verifySsl = (bool) config('services.github.verify_ssl', true);

        $reposResponse = Http::acceptJson()
            ->withHeaders(['User-Agent' => 'BC-Platform'])
            ->withOptions(['verify' => $verifySsl])
            ->timeout(10)
            ->get("https://api.github.com/users/{$username}/repos", [
                'sort' => 'updated',
                'per_page' => 6,
                'type' => 'owner',
            ]);

        $eventsResponse = Http::acceptJson()
            ->withHeaders(['User-Agent' => 'BC-Platform'])
            ->withOptions(['verify' => $verifySsl])
            ->timeout(10)
            ->get("https://api.github.com/users/{$username}/events/public", [
                'per_page' => 30,
            ]);

        $repositories = [];
        if ($reposResponse->ok()) {
            $repos = $reposResponse->json();
            if (is_array($repos)) {
                $repositories = collect($repos)
                    ->filter(fn($repo) => is_array($repo))
                    ->map(fn($repo) => [
                        'name' => (string) ($repo['name'] ?? ''),
                        'url' => (string) ($repo['html_url'] ?? ''),
                        'stars' => (int) ($repo['stargazers_count'] ?? 0),
                        'language' => (string) ($repo['language'] ?? ''),
                        'updated_at' => (string) ($repo['updated_at'] ?? ''),
                    ])
                    ->filter(fn($repo) => $repo['name'] !== '')
                    ->values()
                    ->all();
            }
        }

        $recentCommits = [];
        if ($eventsResponse->ok()) {
            $events = $eventsResponse->json();
            if (is_array($events)) {
                $recentCommits = collect($events)
                    ->filter(fn($event) => is_array($event) && ($event['type'] ?? null) === 'PushEvent')
                    ->flatMap(function ($event) {
                        if (! is_array($event)) {
                            return [];
                        }

                        $repoName = is_array($event['repo'] ?? null)
                            ? (string) (($event['repo']['name'] ?? '') ?: '')
                            : '';

                        $commits = is_array($event['payload']['commits'] ?? null)
                            ? $event['payload']['commits']
                            : [];

                        return collect($commits)
                            ->filter(fn($commit) => is_array($commit))
                            ->map(fn($commit) => [
                                'repo' => $repoName,
                                'message' => (string) ($commit['message'] ?? ''),
                                'sha' => (string) ($commit['sha'] ?? ''),
                                'pushed_at' => (string) ($event['created_at'] ?? ''),
                            ])
                            ->filter(fn($commit) => $commit['message'] !== '')
                            ->values();
                    })
                    ->take(10)
                    ->values()
                    ->all();
            }
        }

        return response()->json([
            'connected' => true,
            'data' => [
                'repositories' => $repositories,
                'recent_commits' => $recentCommits,
            ],
        ]);
    }

    private function githubProvider(): AbstractProvider
    {
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('github');

        $provider->scopes(['read:user', 'user:email']);

        $provider->setHttpClient(new Client([
            'verify' => (bool) config('services.github.verify_ssl', true),
        ]));

        return $provider;
    }

    private function githubConfigError(): ?JsonResponse
    {
        $clientId = trim((string) config('services.github.client_id', ''));
        $clientSecret = trim((string) config('services.github.client_secret', ''));
        $redirectUri = trim((string) config('services.github.redirect', ''));

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            return response()->json([
                'message' => 'GitHub OAuth is not configured. Set GITHUB_CLIENT_ID, GITHUB_CLIENT_SECRET, and GITHUB_REDIRECT_URI on the backend.',
            ], 503);
        }

        return null;
    }
}
