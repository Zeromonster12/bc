<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationStudentProjectProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_progress_for_accepted_application(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Accepted Project',
            'description' => 'Project description.',
            'status' => 'open',
            'max_students' => 2,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('A', 80),
            'status' => 'accepted',
            'student_project_status' => 'not_started',
        ]);

        Sanctum::actingAs($student);

        $response = $this->patchJson('/api/applications/' . $application->id . '/student-progress', [
            'student_project_status' => 'in_progress',
            'student_project_note' => 'Started implementation and setup.',
        ]);

        $response->assertOk()->assertJsonPath('data.student_project_status', 'in_progress');
        $response->assertJsonPath('data.student_project_note', 'Started implementation and setup.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'student_project_status' => 'in_progress',
        ]);
    }

    public function test_student_cannot_update_progress_for_non_accepted_application(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Pending Project',
            'description' => 'Project description.',
            'status' => 'open',
            'max_students' => 2,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('B', 80),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($student);

        $response = $this->patchJson('/api/applications/' . $application->id . '/student-progress', [
            'student_project_status' => 'in_progress',
        ]);

        $response->assertStatus(422);
    }

    public function test_company_can_see_student_progress_in_applications_response(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Tracked Project',
            'description' => 'Project description.',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('C', 80),
            'status' => 'accepted',
            'student_project_status' => 'blocked',
            'student_project_note' => 'Waiting for API credentials from company.',
            'student_project_status_updated_at' => now(),
        ]);

        Sanctum::actingAs($company);

        $response = $this->getJson('/api/applications?project_id=' . $project->id . '&status=accepted');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $application->id);
        $response->assertJsonPath('data.0.student_project_status', 'blocked');
        $response->assertJsonPath('data.0.student_project_note', 'Waiting for API credentials from company.');
    }

    public function test_student_can_submit_timeline_update_for_accepted_application(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Timeline Project',
            'description' => 'Project description.',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('D', 80),
            'status' => 'accepted',
            'student_project_status' => 'not_started',
        ]);

        Sanctum::actingAs($student);

        $response = $this->postJson('/api/applications/' . $application->id . '/progress-updates', [
            'title' => 'Implemented auth callback handling',
            'notes' => 'Finished callback flow and tested edge cases.',
            'student_project_status' => 'in_progress',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'Implemented auth callback handling');
        $response->assertJsonPath('data.student_project_status', 'in_progress');
        $response->assertJsonPath('application.student_project_status', 'in_progress');
        $response->assertJsonPath('application.progress_updates.0.title', 'Implemented auth callback handling');

        $this->assertDatabaseHas('application_progress_updates', [
            'application_id' => $application->id,
            'title' => 'Implemented auth callback handling',
        ]);
    }

    public function test_company_can_list_timeline_updates_for_their_project_application(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Timeline Visibility',
            'description' => 'Project description.',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('E', 80),
            'status' => 'accepted',
        ]);

        Sanctum::actingAs($student);

        $this->postJson('/api/applications/' . $application->id . '/progress-updates', [
            'title' => 'Initial setup done',
            'notes' => 'Repository structure initialized.',
            'student_project_status' => 'in_progress',
        ]);

        Sanctum::actingAs($company);

        $response = $this->getJson('/api/applications/' . $application->id . '/progress-updates');

        $response->assertOk();
        $response->assertJsonPath('data.0.title', 'Initial setup done');
    }
}
