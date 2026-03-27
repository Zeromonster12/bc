<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationTaskCategory;
use App\Models\ApplicationTaskFolder;
use App\Models\ApplicationTask;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    private const TASK_STATUSES = [
        'todo',
        'in_progress',
        'complete',
    ];

    private const TASK_PRIORITIES = [
        'low',
        'medium',
        'high',
        'urgent',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,accepted,rejected,withdrawn'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $query = Application::query()
            ->with([
                'project:id,company_user_id,title,status,location,created_at',
                'project.companyUser:id,name',
                'project.companyUser.companyProfile:id,user_id,profile_data',
                'studentUser:id,name,email',
                'studentUser.studentProfile:id,user_id,avatar_path,profile_data',
                'studentUser.githubAccount:id,user_id,provider,profile_data',
                'tasks:id,application_id,project_id,task_folder_id,task_category_id,created_by_user_id,assignee_user_id,title,requirements,priority,status,position,student_note,due_at,completed_at,created_at',
                'tasks.assignee:id,name,email',
            ])
            ->when($validated['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($validated['project_id'] ?? null, fn($q, $projectId) => $q->where('project_id', $projectId));

        if ($user->role === 'student') {
            $query->where('student_user_id', $user->id);
        } elseif ($user->role === 'company') {
            $query->whereHas('project', fn($projectQuery) => $projectQuery->where('company_user_id', $user->id));
        }

        $applications = $query
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $applications->getCollection()->map(
                fn(Application $application) => $this->transformApplication($application)
            )->values(),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json(['message' => 'Only students can apply to projects.'], 403);
        }

        if ($project->status !== 'open') {
            return response()->json(['message' => 'You can only apply to open projects.'], 422);
        }

        if ($this->isProjectAtCapacity($project->id, $project->max_students)) {
            return response()->json([
                'message' => 'This project is already full. Applications are closed.',
            ], 422);
        }

        $validated = $request->validate([
            'cover_letter' => ['required', 'string', 'min:50', 'max:3000'],
        ]);

        $existingApplication = Application::query()
            ->where('project_id', $project->id)
            ->where('student_user_id', $user->id)
            ->first();

        if ($existingApplication) {
            if (in_array($existingApplication->status, ['pending', 'accepted'], true)) {
                return response()->json(['message' => 'You already applied to this project.'], 422);
            }

            if (in_array($existingApplication->status, ['rejected', 'withdrawn'], true)) {
                if ($existingApplication->tasks()->exists()) {
                    $existingApplication->tasks()->delete();
                }

                $existingApplication->update([
                    'cover_letter' => $validated['cover_letter'],
                    'status' => 'pending',
                    'reviewed_at' => null,
                    'student_project_status' => null,
                    'student_project_note' => null,
                    'student_project_status_updated_at' => null,
                ]);

                $existingApplication->load([
                    'project:id,company_user_id,title,status,location,created_at',
                    'project.companyUser:id,name',
                    'project.companyUser.companyProfile:id,user_id,profile_data',
                    'studentUser:id,name,email',
                    'studentUser.studentProfile:id,user_id,avatar_path,profile_data',
                    'studentUser.githubAccount:id,user_id,provider,profile_data',
                    'tasks:id,application_id,project_id,task_folder_id,task_category_id,created_by_user_id,assignee_user_id,title,requirements,priority,status,position,student_note,due_at,completed_at,created_at',
                    'tasks.assignee:id,name,email',
                ]);

                return response()->json([
                    'data' => $this->transformApplication($existingApplication),
                ]);
            }

            return response()->json(['message' => 'You already applied to this project.'], 422);
        }

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $user->id,
            'cover_letter' => $validated['cover_letter'],
            'status' => 'pending',
        ]);

        $application->load([
            'project:id,company_user_id,title,status,location,created_at',
            'project.companyUser:id,name',
            'project.companyUser.companyProfile:id,user_id,profile_data',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path,profile_data',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'tasks:id,application_id,project_id,task_folder_id,task_category_id,created_by_user_id,assignee_user_id,title,requirements,priority,status,position,student_note,due_at,completed_at,created_at',
            'tasks.assignee:id,name,email',
        ]);

        return response()->json([
            'data' => $this->transformApplication($application),
        ], 201);
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $application->loadMissing('project:id,company_user_id');

        $isOwnerCompany = $application->project?->company_user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwnerCompany && ! $isAdmin) {
            return response()->json(['message' => 'You are not allowed to review this application.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
        ]);

        $application->loadMissing('project:id,company_user_id,max_students');

        if (
            $validated['status'] === 'accepted'
            && $application->status !== 'accepted'
            && $this->isProjectAtCapacity((int) $application->project_id, (int) ($application->project?->max_students ?? 1))
        ) {
            return response()->json([
                'message' => 'This project already reached max students. You cannot accept more applicants.',
            ], 422);
        }

        $updatePayload = [
            'status' => $validated['status'],
            'reviewed_at' => now(),
        ];

        if ($validated['status'] === 'rejected') {
            $application->tasks()->delete();
        }

        $application->update($updatePayload);

        $application->load([
            'project:id,company_user_id,title,status,location,created_at',
            'project.companyUser:id,name',
            'project.companyUser.companyProfile:id,user_id,profile_data',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path,profile_data',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'tasks:id,application_id,project_id,task_folder_id,task_category_id,created_by_user_id,assignee_user_id,title,requirements,priority,status,position,student_note,due_at,completed_at,created_at',
            'tasks.assignee:id,name,email',
        ]);

        return response()->json([
            'data' => $this->transformApplication($application),
        ]);
    }

    public function destroy(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isOwnerStudent = $application->student_user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwnerStudent && ! $isAdmin) {
            return response()->json(['message' => 'You are not allowed to withdraw this application.'], 403);
        }

        if ($isOwnerStudent) {
            $application->update([
                'status' => 'withdrawn',
            ]);
        } else {
            $application->delete();

            return response()->json([
                'message' => 'Application deleted successfully.',
            ]);
        }

        $application->load([
            'project:id,company_user_id,title,status,location,created_at',
            'project.companyUser:id,name',
            'project.companyUser.companyProfile:id,user_id,profile_data',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path,profile_data',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'tasks:id,application_id,project_id,task_folder_id,task_category_id,created_by_user_id,assignee_user_id,title,requirements,priority,status,position,student_note,due_at,completed_at,created_at',
            'tasks.assignee:id,name,email',
        ]);

        return response()->json([
            'data' => $this->transformApplication($application),
        ]);
    }

    public function listTasks(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isOwnerStudent = $application->student_user_id === $user->id;
        $isAdmin = $user->role === 'admin';
        $application->loadMissing('project:id,company_user_id');
        $isOwnerCompany = $application->project?->company_user_id === $user->id;

        if (! $isOwnerStudent && ! $isOwnerCompany && ! $isAdmin) {
            return response()->json([
                'message' => 'You are not allowed to view project updates for this application.',
            ], 403);
        }

        $tasks = $application->tasks()
            ->with('assignee:id,name,email')
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $tasks->map(
                fn(ApplicationTask $task) => $this->transformTask($task)
            )->values(),
        ]);
    }

    public function storeTask(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $application->loadMissing('project:id,company_user_id');

        $isOwnerCompany = $application->project?->company_user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwnerCompany && ! $isAdmin) {
            return response()->json([
                'message' => 'Only the project company can create tasks for this application.',
            ], 403);
        }

        if ($application->status !== 'accepted') {
            return response()->json([
                'message' => 'Tasks can be created only for accepted applications.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', 'in:' . implode(',', self::TASK_PRIORITIES)],
            'assignee_user_id' => ['nullable', 'integer', 'min:1'],
            'task_folder_id' => ['nullable', 'integer', 'min:1'],
            'task_category_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:0'],
            'due_at' => ['nullable', 'date'],
        ]);

        $assigneeId = (int) ($validated['assignee_user_id'] ?? $application->student_user_id);

        if ($assigneeId !== (int) $application->student_user_id) {
            return response()->json([
                'message' => 'Task assignee must be the student assigned to this application.',
            ], 422);
        }

        [$folderId, $categoryId] = $this->resolveTaskGrouping($application->project_id, $validated);

        $task = ApplicationTask::query()->create([
            'application_id' => $application->id,
            'project_id' => $application->project_id,
            'task_folder_id' => $folderId,
            'task_category_id' => $categoryId,
            'created_by_user_id' => $user->id,
            'assignee_user_id' => $assigneeId,
            'title' => $validated['title'],
            'requirements' => $validated['requirements'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'todo',
            'position' => $validated['position'] ?? 0,
            'due_at' => $validated['due_at'] ?? null,
        ]);

        $task->load('assignee:id,name,email');
        $application->load([
            'project:id,company_user_id,title,status,location,created_at',
            'project.companyUser:id,name',
            'project.companyUser.companyProfile:id,user_id,profile_data',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path,profile_data',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'tasks:id,application_id,project_id,task_folder_id,task_category_id,created_by_user_id,assignee_user_id,title,requirements,priority,status,position,student_note,due_at,completed_at,created_at',
            'tasks.assignee:id,name,email',
        ]);

        return response()->json([
            'data' => $this->transformTask($task),
            'application' => $this->transformApplication($application),
        ], 201);
    }

    private function isProjectAtCapacity(int $projectId, int $maxStudents): bool
    {
        $acceptedCount = Application::query()
            ->where('project_id', $projectId)
            ->where('status', 'accepted')
            ->count();

        return $acceptedCount >= max(1, $maxStudents);
    }

    public function updateTask(Request $request, Application $application, ApplicationTask $task): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($task->application_id !== $application->id) {
            return response()->json(['message' => 'Task does not belong to this application.'], 404);
        }

        $application->loadMissing('project:id,company_user_id');

        $isOwnerCompany = $application->project?->company_user_id === $user->id;
        $isAdmin = $user->role === 'admin';
        $isAssignedStudent = $user->role === 'student'
            && (int) $task->assignee_user_id === (int) $user->id
            && (int) $application->student_user_id === (int) $user->id;

        if ($isAssignedStudent) {
            $validated = $request->validate([
                'status' => ['required', 'in:' . implode(',', self::TASK_STATUSES)],
                'student_note' => ['nullable', 'string', 'max:1000'],
                'task_folder_id' => ['nullable', 'integer', 'min:1'],
                'task_category_id' => ['nullable', 'integer', 'min:1'],
                'position' => ['nullable', 'integer', 'min:0'],
            ]);

            $payload = [
                'status' => $validated['status'],
                'student_note' => $validated['student_note'] ?? $task->student_note,
                'completed_at' => $validated['status'] === 'complete' ? now() : null,
            ];

            if (array_key_exists('task_folder_id', $validated) || array_key_exists('task_category_id', $validated)) {
                [$folderId, $categoryId] = $this->resolveTaskGrouping((int) $application->project_id, $validated);
                $payload['task_folder_id'] = $folderId;
                $payload['task_category_id'] = $categoryId;
            }

            if (array_key_exists('position', $validated)) {
                $payload['position'] = $validated['position'];
            }

            $task->update($payload);

            $task->load('assignee:id,name,email');

            return response()->json([
                'data' => $this->transformTask($task),
            ]);
        }

        if (! $isOwnerCompany && ! $isAdmin) {
            return response()->json([
                'message' => 'You are not allowed to update this task.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'in:' . implode(',', self::TASK_PRIORITIES)],
            'status' => ['sometimes', 'in:' . implode(',', self::TASK_STATUSES)],
            'student_note' => ['nullable', 'string', 'max:1000'],
            'assignee_user_id' => ['nullable', 'integer', 'min:1'],
            'task_folder_id' => ['nullable', 'integer', 'min:1'],
            'task_category_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:0'],
            'due_at' => ['nullable', 'date'],
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'No updatable fields were provided.',
            ], 422);
        }

        if (array_key_exists('assignee_user_id', $validated)) {
            $assigneeId = (int) $validated['assignee_user_id'];
            if ($assigneeId !== (int) $application->student_user_id) {
                return response()->json([
                    'message' => 'Task assignee must be the student assigned to this application.',
                ], 422);
            }
        }

        $payload = $validated;
        if (array_key_exists('task_folder_id', $validated) || array_key_exists('task_category_id', $validated)) {
            [$folderId, $categoryId] = $this->resolveTaskGrouping($application->project_id, $validated);
            $payload['task_folder_id'] = $folderId;
            $payload['task_category_id'] = $categoryId;
        }
        if (array_key_exists('status', $payload)) {
            $payload['completed_at'] = $payload['status'] === 'complete' ? now() : null;
        }

        $task->update($payload);
        $task->load('assignee:id,name,email');

        return response()->json([
            'data' => $this->transformTask($task),
        ]);
    }

    public function destroyTask(Request $request, Application $application, ApplicationTask $task): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($task->application_id !== $application->id) {
            return response()->json(['message' => 'Task does not belong to this application.'], 404);
        }

        $application->loadMissing('project:id,company_user_id');
        $isOwnerCompany = $application->project?->company_user_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (! $isOwnerCompany && ! $isAdmin) {
            return response()->json([
                'message' => 'You are not allowed to delete this task.',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    private function transformApplication(Application $application): array
    {
        $githubAccount = $application->studentUser?->githubAccount;
        $githubProfileData = is_array($githubAccount?->profile_data) ? $githubAccount->profile_data : [];
        $studentProfileData = is_array($application->studentUser?->studentProfile?->profile_data)
            ? $application->studentUser->studentProfile->profile_data
            : [];
        $companyProfileData = is_array($application->project?->companyUser?->companyProfile?->profile_data)
            ? $application->project->companyUser->companyProfile->profile_data
            : [];
        $companyName = trim((string) ($companyProfileData['business_name']
            ?? $companyProfileData['name']
            ?? $application->project?->companyUser?->name
            ?? ''));

        return [
            'id' => $application->id,
            'project_id' => $application->project_id,
            'student_user_id' => $application->student_user_id,
            'project' => [
                'id' => $application->project?->id,
                'title' => $application->project?->title,
                'status' => $application->project?->status,
                'location' => $application->project?->location,
                'requirements' => $application->project?->requirements,
                'tech_stack' => is_array($application->project?->tech_stack) ? $application->project->tech_stack : [],
                'posted_at' => optional($application->project?->created_at)?->toISOString(),
                'company' => [
                    'user_id' => $application->project?->company_user_id,
                    'name' => $companyName !== '' ? $companyName : null,
                ],
            ],
            'student' => [
                'id' => $application->studentUser?->id,
                'name' => $application->studentUser?->name,
                'email' => $application->studentUser?->email,
                'avatar_url' => $application->studentUser?->avatar_url,
                'github_connected' => (bool) $githubAccount,
                'github_username' => (string) ($githubProfileData['nickname'] ?? ''),
                'github_url' => (string) ($githubProfileData['html_url'] ?? ''),
                'profile' => [
                    'university' => (string) ($studentProfileData['university'] ?? ''),
                    'degree' => (string) ($studentProfileData['degree'] ?? ''),
                    'field_of_study' => (string) ($studentProfileData['field_of_study'] ?? ''),
                    'skills' => is_array($studentProfileData['skills'] ?? null) ? $studentProfileData['skills'] : [],
                    'interests' => is_array($studentProfileData['interests'] ?? null) ? $studentProfileData['interests'] : [],
                    'projects' => is_array($studentProfileData['projects'] ?? null) ? $studentProfileData['projects'] : [],
                    'bio' => (string) ($studentProfileData['bio'] ?? ''),
                    'about_me' => (string) ($studentProfileData['about_me'] ?? ''),
                ],
            ],
            'cover_letter' => $application->cover_letter,
            'status' => $application->status,
            'tasks' => $application->tasks
                ? $application->tasks->map(
                    fn(ApplicationTask $task) => $this->transformTask($task)
                )->values()
                : [],
            'reviewed_at' => optional($application->reviewed_at)?->toISOString(),
            'created_at' => optional($application->created_at)?->toISOString(),
        ];
    }

    private function transformTask(ApplicationTask $task): array
    {
        return [
            'id' => $task->id,
            'application_id' => $task->application_id,
            'project_id' => $task->project_id,
            'task_folder_id' => $task->task_folder_id,
            'task_category_id' => $task->task_category_id,
            'title' => $task->title,
            'requirements' => $task->requirements,
            'priority' => $task->priority,
            'status' => $task->status,
            'position' => (int) $task->position,
            'student_note' => $task->student_note,
            'due_at' => optional($task->due_at)?->toISOString(),
            'completed_at' => optional($task->completed_at)?->toISOString(),
            'created_at' => optional($task->created_at)?->toISOString(),
            'assignee' => [
                'id' => $task->assignee_user_id,
                'name' => $task->assignee?->name,
                'email' => $task->assignee?->email,
            ],
        ];
    }

    private function resolveTaskGrouping(int $projectId, array $validated): array
    {
        $folderId = $this->normalizeNullablePositiveInt($validated, 'task_folder_id');
        $categoryId = $this->normalizeNullablePositiveInt($validated, 'task_category_id');

        if ($folderId !== null) {
            $folder = ApplicationTaskFolder::query()->find($folderId);
            if (! $folder || (int) $folder->project_id !== $projectId) {
                abort(response()->json([
                    'message' => 'Selected folder does not belong to this project.',
                ], 422));
            }
        }

        if ($categoryId !== null) {
            $category = ApplicationTaskCategory::query()->find($categoryId);
            if (! $category || (int) $category->project_id !== $projectId) {
                abort(response()->json([
                    'message' => 'Selected category does not belong to this project.',
                ], 422));
            }

            if ($folderId !== null && (int) $category->task_folder_id !== $folderId) {
                abort(response()->json([
                    'message' => 'Selected category does not belong to selected folder.',
                ], 422));
            }

            if ($folderId === null) {
                $folderId = (int) $category->task_folder_id;
            }
        }

        return [$folderId, $categoryId];
    }

    private function normalizeNullablePositiveInt(array $validated, string $key): ?int
    {
        if (! array_key_exists($key, $validated) || $validated[$key] === null || $validated[$key] === '') {
            return null;
        }

        return (int) $validated[$key];
    }
}
