<?php

namespace App\Http\Controllers;

use App\Models\ApplicationTask;
use App\Models\ApplicationTaskCategory;
use App\Models\ApplicationTaskFolder;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rule;

class ProjectTaskBoardController extends Controller
{
    private const BOARD_STATUSES = ['todo', 'in_progress', 'complete'];

    public function show(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to access this project board.'], 403);
        }

        $folders = ApplicationTaskFolder::query()
            ->where('project_id', $project->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $categories = ApplicationTaskCategory::query()
            ->where('project_id', $project->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $tasks = ApplicationTask::query()
            ->with(['assignee:id,name,email'])
            ->where('project_id', $project->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $sections = [];
        foreach (self::BOARD_STATUSES as $status) {
            $sections[$status] = $this->buildSection($status, $folders, $categories, $tasks);
        }

        return response()->json([
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'title' => $project->title,
                ],
                'counts' => [
                    'todo' => $tasks->where('status', 'todo')->count(),
                    'in_progress' => $tasks->where('status', 'in_progress')->count(),
                    'complete' => $tasks->where('status', 'complete')->count(),
                ],
                'sections' => $sections,
            ],
        ]);
    }

    public function listFolders(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to access folders for this project.'], 403);
        }

        $folders = ApplicationTaskFolder::query()
            ->with(['categories:id,project_id,task_folder_id,name,color,position'])
            ->where('project_id', $project->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $folders->map(fn(ApplicationTaskFolder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
                'position' => $folder->position,
                'parent_folder_id' => $folder->parent_folder_id,
                'categories' => $folder->categories->map(fn(ApplicationTaskCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'position' => $category->position,
                ])->values(),
            ])->values(),
        ]);
    }

    public function storeFolder(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to manage folders for this project.'], 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('application_task_folders', 'name')
                    ->where(fn($query) => $query->where('project_id', $project->id)),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0'],
            'parent_folder_id' => [
                'nullable',
                'integer',
                (new Exists('application_task_folders', 'id'))
                    ->where(fn($query) => $query->where('project_id', $project->id)),
            ],
        ]);

        $folder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'position' => $validated['position'] ?? 0,
            'parent_folder_id' => $validated['parent_folder_id'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
                'position' => $folder->position,
                'parent_folder_id' => $folder->parent_folder_id,
            ],
        ], 201);
    }

    public function updateFolder(Request $request, Project $project, ApplicationTaskFolder $folder): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to manage folders for this project.'], 403);
        }

        if ((int) $folder->project_id !== (int) $project->id) {
            return response()->json(['message' => 'Folder does not belong to this project.'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:120',
                Rule::unique('application_task_folders', 'name')
                    ->where(fn($query) => $query->where('project_id', $project->id))
                    ->ignore($folder->id),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0'],
            'parent_folder_id' => [
                'nullable',
                'integer',
                (new Exists('application_task_folders', 'id'))
                    ->where(fn($query) => $query->where('project_id', $project->id)),
            ],
        ]);

        if (
            array_key_exists('parent_folder_id', $validated)
            && (int) ($validated['parent_folder_id'] ?? 0) === (int) $folder->id
        ) {
            return response()->json(['message' => 'Folder cannot be its own parent.'], 422);
        }

        if (
            array_key_exists('parent_folder_id', $validated)
            && $this->wouldCreateFolderCycle($project->id, $folder->id, $validated['parent_folder_id'] ?? null)
        ) {
            return response()->json(['message' => 'Folder nesting would create a cycle.'], 422);
        }

        if ($validated === []) {
            return response()->json(['message' => 'No updatable fields were provided.'], 422);
        }

        $folder->update($validated);

        return response()->json([
            'data' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'color' => $folder->color,
                'position' => $folder->position,
                'parent_folder_id' => $folder->parent_folder_id,
            ],
        ]);
    }

    public function destroyFolder(Request $request, Project $project, ApplicationTaskFolder $folder): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to manage folders for this project.'], 403);
        }

        if ((int) $folder->project_id !== (int) $project->id) {
            return response()->json(['message' => 'Folder does not belong to this project.'], 404);
        }

        $folder->delete();

        return response()->json(['message' => 'Folder deleted successfully.']);
    }

    public function storeCategory(Request $request, Project $project, ApplicationTaskFolder $folder): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to manage categories for this project.'], 403);
        }

        if ((int) $folder->project_id !== (int) $project->id) {
            return response()->json(['message' => 'Folder does not belong to this project.'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('application_task_categories', 'name')
                    ->where(fn($query) => $query->where('task_folder_id', $folder->id)),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = ApplicationTaskCategory::query()->create([
            'project_id' => $project->id,
            'task_folder_id' => $folder->id,
            'created_by_user_id' => $user->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'position' => $validated['position'] ?? 0,
        ]);

        return response()->json([
            'data' => [
                'id' => $category->id,
                'task_folder_id' => $category->task_folder_id,
                'name' => $category->name,
                'color' => $category->color,
                'position' => $category->position,
            ],
        ], 201);
    }

    public function updateCategory(
        Request $request,
        Project $project,
        ApplicationTaskFolder $folder,
        ApplicationTaskCategory $category
    ): JsonResponse {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to manage categories for this project.'], 403);
        }

        if (
            (int) $folder->project_id !== (int) $project->id
            || (int) $category->project_id !== (int) $project->id
            || (int) $category->task_folder_id !== (int) $folder->id
        ) {
            return response()->json(['message' => 'Category does not belong to this project folder.'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:120',
                Rule::unique('application_task_categories', 'name')
                    ->where(fn($query) => $query->where('task_folder_id', $folder->id))
                    ->ignore($category->id),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validated === []) {
            return response()->json(['message' => 'No updatable fields were provided.'], 422);
        }

        $category->update($validated);

        return response()->json([
            'data' => [
                'id' => $category->id,
                'task_folder_id' => $category->task_folder_id,
                'name' => $category->name,
                'color' => $category->color,
                'position' => $category->position,
            ],
        ]);
    }

    public function destroyCategory(
        Request $request,
        Project $project,
        ApplicationTaskFolder $folder,
        ApplicationTaskCategory $category
    ): JsonResponse {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageProjectTasks($project, $user->id, $user->role)) {
            return response()->json(['message' => 'You are not allowed to manage categories for this project.'], 403);
        }

        if (
            (int) $folder->project_id !== (int) $project->id
            || (int) $category->project_id !== (int) $project->id
            || (int) $category->task_folder_id !== (int) $folder->id
        ) {
            return response()->json(['message' => 'Category does not belong to this project folder.'], 404);
        }

        if ($category->tasks()->exists()) {
            return response()->json([
                'message' => 'Category contains tasks. Move tasks to another category before deleting.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    private function buildSection($status, $folders, $categories, $tasks): array
    {
        $statusTasks = $tasks->where('status', $status)->values();

        return $folders->map(function (ApplicationTaskFolder $folder) use ($categories, $statusTasks): array {
            $folderCategories = $categories->where('task_folder_id', $folder->id)->values();
            $uncategorizedTasks = $statusTasks
                ->where('task_folder_id', $folder->id)
                ->where('task_category_id', null)
                ->values();

            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'position' => $folder->position,
                'parent_folder_id' => $folder->parent_folder_id,
                'uncategorized_tasks' => $uncategorizedTasks
                    ->map(fn(ApplicationTask $task) => $this->transformBoardTask($task))
                    ->values(),
                'categories' => $folderCategories->map(function (ApplicationTaskCategory $category) use ($statusTasks): array {
                    $categoryTasks = $statusTasks
                        ->where('task_category_id', $category->id)
                        ->values();

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'position' => $category->position,
                        'tasks' => $categoryTasks->map(fn(ApplicationTask $task) => $this->transformBoardTask($task))->values(),
                    ];
                })->values(),
            ];
        })->values()->all();
    }

    private function transformBoardTask(ApplicationTask $task): array
    {
        return [
            'id' => $task->id,
            'application_id' => $task->application_id,
            'title' => $task->title,
            'priority' => $task->priority,
            'status' => $task->status,
            'position' => (int) $task->position,
            'assignee' => [
                'id' => $task->assignee_user_id,
                'name' => $task->assignee?->name,
                'email' => $task->assignee?->email,
            ],
        ];
    }

    private function canManageProjectTasks(Project $project, int $userId, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }

        return $role === 'company' && (int) $project->company_user_id === $userId;
    }

    private function wouldCreateFolderCycle(int $projectId, int $folderId, $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        $currentId = (int) $newParentId;
        while ($currentId > 0) {
            if ($currentId === $folderId) {
                return true;
            }

            $currentId = (int) (ApplicationTaskFolder::query()
                ->where('project_id', $projectId)
                ->where('id', $currentId)
                ->value('parent_folder_id') ?? 0);
        }

        return false;
    }
}
