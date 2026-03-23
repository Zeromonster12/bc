<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Project;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationGitHubVisibilityTest extends TestCase
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

    public function test_company_can_see_student_github_profile_in_applications_response(): void
    {
        $company = User::factory()->create(['role' => 'company']);
        $student = User::factory()->create(['role' => 'student']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Backend Intern',
            'description' => 'Build API features and integrations.',
            'requirements' => 'Laravel, SQL, and API basics.',
            'status' => 'open',
            'max_students' => 2,
        ]);

        Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('I am interested in this role. ', 4),
            'status' => 'pending',
        ]);

        SocialAccount::query()->create([
            'user_id' => $student->id,
            'provider' => 'github',
            'provider_user_id' => 'github-123',
            'avatar_url' => null,
            'profile_data' => [
                'nickname' => 'octocat',
                'html_url' => 'https://github.com/octocat',
            ],
        ]);

        Sanctum::actingAs($company);

        $response = $this->getJson('/api/applications?project_id=' . $project->id);

        $response->assertOk()->assertJsonPath('data.0.student.github_connected', true);
        $response->assertJsonPath('data.0.student.github_username', 'octocat');
        $response->assertJsonPath('data.0.student.github_url', 'https://github.com/octocat');
    }
}
