<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationTask;
use App\Models\ApplicationTaskCategory;
use App\Models\ApplicationTaskFolder;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if (! $this->canViewProjectTasks($project, $user->id, $user->role)) {
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

        if (! $this->canViewProjectTasks($project, $user->id, $user->role)) {
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
                'position' => $folder->position,
                'status' => $folder->status,
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
            'position' => ['nullable', 'integer', 'min:0'],
            'parent_folder_id' => [
                'nullable',
                'integer',
                (new Exists('application_task_folders', 'id'))
                    ->where(fn($query) => $query->where('project_id', $project->id)),
            ],
            'status' => ['nullable', Rule::in(self::BOARD_STATUSES)],
        ]);

        $folder = ApplicationTaskFolder::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'name' => $validated['name'],
            'position' => $validated['position'] ?? 0,
            'parent_folder_id' => $validated['parent_folder_id'] ?? null,
            'status' => $validated['status'] ?? 'todo',
        ]);

        return response()->json([
            'data' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'position' => $folder->position,
                'status' => $folder->status,
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

        $canManageFolders = $this->canManageProjectTasks($project, $user->id, $user->role);
        $canMoveFolders = $this->canViewProjectTasks($project, $user->id, $user->role);

        if (! $canManageFolders && ! $canMoveFolders) {
            return response()->json(['message' => 'You are not allowed to move folders for this project.'], 403);
        }

        if ((int) $folder->project_id !== (int) $project->id) {
            return response()->json(['message' => 'Folder does not belong to this project.'], 404);
        }

        $validated = $request->validate(
            $canManageFolders
                ? [
                    'name' => [
                        'sometimes',
                        'string',
                        'max:120',
                        Rule::unique('application_task_folders', 'name')
                            ->where(fn($query) => $query->where('project_id', $project->id))
                            ->ignore($folder->id),
                    ],
                    'position' => ['nullable', 'integer', 'min:0'],
                    'parent_folder_id' => [
                        'nullable',
                        'integer',
                        (new Exists('application_task_folders', 'id'))
                            ->where(fn($query) => $query->where('project_id', $project->id)),
                    ],
                    'status' => ['sometimes', 'nullable', Rule::in(self::BOARD_STATUSES)],
                ]
                : [
                    'position' => ['nullable', 'integer', 'min:0'],
                    'parent_folder_id' => [
                        'nullable',
                        'integer',
                        (new Exists('application_task_folders', 'id'))
                            ->where(fn($query) => $query->where('project_id', $project->id)),
                    ],
                    'status' => ['sometimes', 'nullable', Rule::in(self::BOARD_STATUSES)],
                ]
        );

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

        $statusProvided = array_key_exists('status', $validated);
        $nextStatus = $validated['status'] ?? null;

        DB::transaction(function () use ($project, $folder, $validated, $statusProvided, $nextStatus): void {
            if ($statusProvided && $nextStatus !== null) {
                $subtreeFolderIds = $this->collectFolderSubtreeIds($project->id, $folder->id);

                ApplicationTaskFolder::query()
                    ->whereIn('id', $subtreeFolderIds)
                    ->update(['status' => $nextStatus]);

                ApplicationTask::query()
                    ->where('project_id', $project->id)
                    ->whereIn('task_folder_id', $subtreeFolderIds)
                    ->update([
                        'status' => $nextStatus,
                        'completed_at' => $nextStatus === 'complete' ? now() : null,
                    ]);

                $nonStatusPayload = $validated;
                unset($nonStatusPayload['status']);

                if ($nonStatusPayload !== []) {
                    $folder->update($nonStatusPayload);
                }

                return;
            }

            $folder->update($validated);
        });

        $folder->refresh();

        return response()->json([
            'data' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'position' => $folder->position,
                'status' => $folder->status,
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
        $statusFolders = $folders
            ->filter(fn(ApplicationTaskFolder $folder) => $folder->status === null || $folder->status === $status)
            ->values();
        $standaloneTasks = $statusTasks
            ->where('task_folder_id', null)
            ->values();

        $sectionFolders = $statusFolders->map(function (ApplicationTaskFolder $folder) use ($categories, $statusTasks): array {
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
                'is_virtual' => false,
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
        })->values();

        if ($standaloneTasks->isNotEmpty()) {
            $sectionFolders = collect([[
                'id' => 0,
                'name' => 'No folder',
                'position' => -1,
                'parent_folder_id' => null,
                'is_virtual' => true,
                'uncategorized_tasks' => $standaloneTasks
                    ->map(fn(ApplicationTask $task) => $this->transformBoardTask($task))
                    ->values(),
                'categories' => [],
            ]])->concat($sectionFolders)->values();
        }

        return $sectionFolders->all();
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

    private function canViewProjectTasks(Project $project, int $userId, string $role): bool
    {
        if ($this->canManageProjectTasks($project, $userId, $role)) {
            return true;
        }

        if ($role !== 'student') {
            return false;
        }

        return Application::query()
            ->where('project_id', $project->id)
            ->where('student_user_id', $userId)
            ->where('status', 'accepted')
            ->exists();
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

    private function collectFolderSubtreeIds(int $projectId, int $rootFolderId): array
    {
        $rows = ApplicationTaskFolder::query()
            ->where('project_id', $projectId)
            ->get(['id', 'parent_folder_id']);

        $childrenByParent = [];
        foreach ($rows as $row) {
            $parentId = $row->parent_folder_id === null ? null : (int) $row->parent_folder_id;
            $childrenByParent[$parentId] ??= [];
            $childrenByParent[$parentId][] = (int) $row->id;
        }

        $result = [];
        $stack = [$rootFolderId];
        while ($stack !== []) {
            $currentId = array_pop($stack);
            if (in_array($currentId, $result, true)) {
                continue;
            }

            $result[] = $currentId;
            foreach ($childrenByParent[$currentId] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $result;
    }
}
