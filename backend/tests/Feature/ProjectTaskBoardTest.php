<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationTask;
use App\Models\ApplicationTaskCategory;
use App\Models\ApplicationTaskFolder;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTaskBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_view_project_task_board_grouped_by_status_folder_and_category(): void
    {
        $company = User::factory()->create(['role' => 'company']);
        $student = User::factory()->create(['role' => 'student']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Board project',
            'description' => 'Description',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('X', 80),
            'status' => 'accepted',
        ]);

        $folder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'name' => 'Integrations',
            'position' => 0,
        ]);

        $category = ApplicationTaskCategory::query()->create([
            'project_id' => $project->id,
            'task_folder_id' => $folder->id,
            'created_by_user_id' => $company->id,
            'name' => 'Analytics',
            'position' => 0,
        ]);

        ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $project->id,
            'task_folder_id' => $folder->id,
            'task_category_id' => $category->id,
            'created_by_user_id' => $company->id,
            'assignee_user_id' => $student->id,
            'title' => 'Connect analytics SDK',
            'priority' => 'high',
            'status' => 'todo',
            'position' => 1,
        ]);

        Sanctum::actingAs($company);

        $response = $this->getJson('/api/projects/' . $project->id . '/task-board');

        $response->assertOk();
        $response->assertJsonPath('data.project.id', $project->id);
        $response->assertJsonPath('data.sections.todo.0.name', 'Integrations');
        $response->assertJsonPath('data.sections.todo.0.categories.0.name', 'Analytics');
        $response->assertJsonPath('data.sections.todo.0.categories.0.tasks.0.title', 'Connect analytics SDK');
    }

    public function test_student_cannot_access_company_project_task_board(): void
    {
        $company = User::factory()->create(['role' => 'company']);
        $student = User::factory()->create(['role' => 'student']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Board project',
            'description' => 'Description',
            'status' => 'open',
            'max_students' => 1,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/projects/' . $project->id . '/task-board');

        $response->assertStatus(403);
    }

    public function test_company_can_create_folder_and_category(): void
    {
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Board project',
            'description' => 'Description',
            'status' => 'open',
            'max_students' => 1,
        ]);

        Sanctum::actingAs($company);

        $folderResponse = $this->postJson('/api/projects/' . $project->id . '/task-folders', [
            'name' => 'Integrations',
        ]);

        $folderResponse->assertCreated();
        $folderId = (int) $folderResponse->json('data.id');

        $categoryResponse = $this->postJson('/api/projects/' . $project->id . '/task-folders/' . $folderId . '/categories', [
            'name' => 'Analytics',
        ]);

        $categoryResponse->assertCreated();

        $this->assertDatabaseHas('application_task_folders', [
            'project_id' => $project->id,
            'name' => 'Integrations',
        ]);

        $this->assertDatabaseHas('application_task_categories', [
            'project_id' => $project->id,
            'task_folder_id' => $folderId,
            'name' => 'Analytics',
        ]);
    }

    public function test_company_can_move_folder_under_another_folder(): void
    {
        $company = User::factory()->create(['role' => 'company']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Board project',
            'description' => 'Description',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $parentFolder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'name' => 'Parent',
            'position' => 1,
        ]);

        $childFolder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'name' => 'Child',
            'position' => 2,
        ]);

        Sanctum::actingAs($company);

        $response = $this->patchJson('/api/projects/' . $project->id . '/task-folders/' . $childFolder->id, [
            'parent_folder_id' => $parentFolder->id,
            'position' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.parent_folder_id', $parentFolder->id);

        $this->assertDatabaseHas('application_task_folders', [
            'id' => $childFolder->id,
            'parent_folder_id' => $parentFolder->id,
        ]);
    }

    public function test_company_can_move_task_to_folder_without_category(): void
    {
        $company = User::factory()->create(['role' => 'company']);
        $student = User::factory()->create(['role' => 'student']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Board project',
            'description' => 'Description',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('X', 80),
            'status' => 'accepted',
        ]);

        $folder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'name' => 'Root',
            'position' => 1,
        ]);

        $category = ApplicationTaskCategory::query()->create([
            'project_id' => $project->id,
            'task_folder_id' => $folder->id,
            'created_by_user_id' => $company->id,
            'name' => 'Sub',
            'position' => 1,
        ]);

        $task = ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $project->id,
            'task_folder_id' => $folder->id,
            'task_category_id' => $category->id,
            'created_by_user_id' => $company->id,
            'assignee_user_id' => $student->id,
            'title' => 'Move me',
            'priority' => 'medium',
            'status' => 'todo',
            'position' => 1,
        ]);

        Sanctum::actingAs($company);

        $response = $this->patchJson('/api/applications/' . $application->id . '/tasks/' . $task->id, [
            'task_folder_id' => $folder->id,
            'task_category_id' => null,
            'status' => 'todo',
            'position' => 2,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.task_folder_id', $folder->id);
        $response->assertJsonPath('data.task_category_id', null);

        $this->assertDatabaseHas('application_tasks', [
            'id' => $task->id,
            'task_folder_id' => $folder->id,
            'task_category_id' => null,
        ]);
    }

    public function test_company_can_delete_folder_even_when_it_contains_tasks(): void
    {
        $company = User::factory()->create(['role' => 'company']);
        $student = User::factory()->create(['role' => 'student']);

        $project = Project::query()->create([
            'company_user_id' => $company->id,
            'title' => 'Board project',
            'description' => 'Description',
            'status' => 'open',
            'max_students' => 1,
        ]);

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $student->id,
            'cover_letter' => str_repeat('X', 80),
            'status' => 'accepted',
        ]);

        $folder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $company->id,
            'name' => 'Deletable',
            'position' => 1,
        ]);

        $task = ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $project->id,
            'task_folder_id' => $folder->id,
            'task_category_id' => null,
            'created_by_user_id' => $company->id,
            'assignee_user_id' => $student->id,
            'title' => 'Task in folder',
            'priority' => 'medium',
            'status' => 'todo',
            'position' => 1,
        ]);

        Sanctum::actingAs($company);

        $response = $this->deleteJson('/api/projects/' . $project->id . '/task-folders/' . $folder->id);

        $response->assertOk();
        $this->assertDatabaseMissing('application_task_folders', ['id' => $folder->id]);
        $this->assertDatabaseHas('application_tasks', [
            'id' => $task->id,
            'task_folder_id' => null,
        ]);
    }
}
