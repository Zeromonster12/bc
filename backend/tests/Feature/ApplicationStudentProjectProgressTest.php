<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationTask;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationStudentProjectProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_create_task_for_accepted_application(): void
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
        ]);

        Sanctum::actingAs($company);

        $response = $this->postJson('/api/applications/' . $application->id . '/tasks', [
            'title' => 'Implement API endpoint',
            'priority' => 'high',
            'requirements' => 'Create endpoint with validation and tests.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'Implement API endpoint');
        $response->assertJsonPath('data.priority', 'high');
        $response->assertJsonPath('data.status', 'todo');
        $response->assertJsonPath('data.assignee.id', $student->id);

        $this->assertDatabaseHas('application_tasks', [
            'application_id' => $application->id,
            'title' => 'Implement API endpoint',
            'priority' => 'high',
            'status' => 'todo',
        ]);
    }

    public function test_student_cannot_create_task(): void
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
            'status' => 'accepted',
        ]);

        Sanctum::actingAs($student);

        $response = $this->postJson('/api/applications/' . $application->id . '/tasks', [
            'title' => 'Should fail',
            'priority' => 'medium',
        ]);

        $response->assertStatus(403);
    }

    public function test_assigned_student_can_update_task_status_and_note(): void
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
        ]);

        $task = ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'assignee_user_id' => $student->id,
            'title' => 'Build frontend form',
            'requirements' => 'Implement Vue form with validation.',
            'priority' => 'medium',
            'status' => 'todo',
        ]);

        Sanctum::actingAs($student);

        $response = $this->patchJson('/api/applications/' . $application->id . '/tasks/' . $task->id, [
            'status' => 'in_progress',
            'student_note' => 'Form skeleton done, adding validation now.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'in_progress');
        $response->assertJsonPath('data.student_note', 'Form skeleton done, adding validation now.');

        $this->assertDatabaseHas('application_tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_unrelated_student_cannot_update_task(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $anotherStudent = User::factory()->create(['role' => 'student']);
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
        ]);

        $task = ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'assignee_user_id' => $student->id,
            'title' => 'Implement auth callback',
            'requirements' => 'Support success and error response handling.',
            'priority' => 'high',
            'status' => 'todo',
        ]);

        Sanctum::actingAs($anotherStudent);

        $response = $this->patchJson('/api/applications/' . $application->id . '/tasks/' . $task->id, [
            'status' => 'complete',
        ]);

        $response->assertStatus(403);
    }

    public function test_application_response_contains_tasks_instead_of_progress_updates(): void
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

        ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'assignee_user_id' => $student->id,
            'title' => 'Initial setup',
            'requirements' => 'Initialize repository and base modules.',
            'priority' => 'low',
            'status' => 'todo',
        ]);

        Sanctum::actingAs($company);

        $response = $this->getJson('/api/applications?project_id=' . $project->id . '&status=accepted');

        $response->assertOk();
        $response->assertJsonPath('data.0.tasks.0.title', 'Initial setup');
        $response->assertJsonMissingPath('data.0.progress_updates');
    }
}
