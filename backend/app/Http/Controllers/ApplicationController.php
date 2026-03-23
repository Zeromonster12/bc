<?php

namespace App\Http\Controllers;

use App\Models\ApplicationProgressUpdate;
use App\Models\Application;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    private const STUDENT_PROJECT_PROGRESS_STATUSES = [
        'not_started',
        'in_progress',
        'blocked',
        'completed',
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
                'project:id,company_user_id,title,status,deadline',
                'project.companyUser:id,name',
                'studentUser:id,name,email',
                'studentUser.studentProfile:id,user_id,avatar_path',
                'studentUser.githubAccount:id,user_id,provider,profile_data',
                'progressUpdates:id,application_id,student_user_id,title,notes,student_project_status,created_at',
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

        $validated = $request->validate([
            'cover_letter' => ['required', 'string', 'min:50', 'max:3000'],
        ]);

        $alreadyApplied = Application::query()
            ->where('project_id', $project->id)
            ->where('student_user_id', $user->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['message' => 'You already applied to this project.'], 422);
        }

        $application = Application::query()->create([
            'project_id' => $project->id,
            'student_user_id' => $user->id,
            'cover_letter' => $validated['cover_letter'],
            'status' => 'pending',
        ]);

        $application->load([
            'project:id,company_user_id,title,status,deadline',
            'project.companyUser:id,name',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'progressUpdates:id,application_id,student_user_id,title,notes,student_project_status,created_at',
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

        $updatePayload = [
            'status' => $validated['status'],
            'reviewed_at' => now(),
        ];

        if ($validated['status'] === 'accepted') {
            $updatePayload['student_project_status'] = $application->student_project_status ?: 'not_started';
            $updatePayload['student_project_status_updated_at'] = now();
        }

        if ($validated['status'] === 'rejected') {
            $updatePayload['student_project_status'] = null;
            $updatePayload['student_project_note'] = null;
            $updatePayload['student_project_status_updated_at'] = null;
        }

        $application->update($updatePayload);

        $application->load([
            'project:id,company_user_id,title,status,deadline',
            'project.companyUser:id,name',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'progressUpdates:id,application_id,student_user_id,title,notes,student_project_status,created_at',
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
            'project:id,company_user_id,title,status,deadline',
            'project.companyUser:id,name',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'progressUpdates:id,application_id,student_user_id,title,notes,student_project_status,created_at',
        ]);

        return response()->json([
            'data' => $this->transformApplication($application),
        ]);
    }

    public function updateStudentProgress(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== 'student' || $application->student_user_id !== $user->id) {
            return response()->json(['message' => 'You are not allowed to update this project progress.'], 403);
        }

        if ($application->status !== 'accepted') {
            return response()->json([
                'message' => 'Progress can be updated only for accepted applications.',
            ], 422);
        }

        $validated = $request->validate([
            'student_project_status' => ['required', 'in:' . implode(',', self::STUDENT_PROJECT_PROGRESS_STATUSES)],
            'student_project_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $application->update([
            'student_project_status' => $validated['student_project_status'],
            'student_project_note' => $validated['student_project_note'] ?? null,
            'student_project_status_updated_at' => now(),
        ]);

        $application->load([
            'project:id,company_user_id,title,status,deadline',
            'project.companyUser:id,name',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'progressUpdates:id,application_id,student_user_id,title,notes,student_project_status,created_at',
        ]);

        return response()->json([
            'data' => $this->transformApplication($application),
        ]);
    }

    public function listProgressUpdates(Request $request, Application $application): JsonResponse
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

        $updates = $application->progressUpdates()
            ->with('studentUser:id,name')
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $updates->map(
                fn(ApplicationProgressUpdate $update) => $this->transformProgressUpdate($update)
            )->values(),
        ]);
    }

    public function storeProgressUpdate(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== 'student' || $application->student_user_id !== $user->id) {
            return response()->json([
                'message' => 'Only the assigned student can submit project updates.',
            ], 403);
        }

        if ($application->status !== 'accepted') {
            return response()->json([
                'message' => 'Project updates can be submitted only for accepted applications.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'student_project_status' => ['nullable', 'in:' . implode(',', self::STUDENT_PROJECT_PROGRESS_STATUSES)],
        ]);

        $statusForUpdate = $validated['student_project_status'] ?? $application->student_project_status ?? 'not_started';

        $progressUpdate = ApplicationProgressUpdate::query()->create([
            'application_id' => $application->id,
            'student_user_id' => $user->id,
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
            'student_project_status' => $statusForUpdate,
        ]);

        $application->update([
            'student_project_status' => $statusForUpdate,
            'student_project_note' => $validated['notes'] ?? null,
            'student_project_status_updated_at' => now(),
        ]);

        $progressUpdate->load('studentUser:id,name');
        $application->load([
            'project:id,company_user_id,title,status,deadline',
            'project.companyUser:id,name',
            'studentUser:id,name,email',
            'studentUser.studentProfile:id,user_id,avatar_path',
            'studentUser.githubAccount:id,user_id,provider,profile_data',
            'progressUpdates:id,application_id,student_user_id,title,notes,student_project_status,created_at',
        ]);

        return response()->json([
            'data' => $this->transformProgressUpdate($progressUpdate),
            'application' => $this->transformApplication($application),
        ], 201);
    }

    private function transformApplication(Application $application): array
    {
        $githubAccount = $application->studentUser?->githubAccount;
        $githubProfileData = is_array($githubAccount?->profile_data) ? $githubAccount->profile_data : [];

        return [
            'id' => $application->id,
            'project_id' => $application->project_id,
            'student_user_id' => $application->student_user_id,
            'project' => [
                'id' => $application->project?->id,
                'title' => $application->project?->title,
                'status' => $application->project?->status,
                'deadline' => optional($application->project?->deadline)?->toDateString(),
                'company' => [
                    'name' => $application->project?->companyUser?->name,
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
            ],
            'cover_letter' => $application->cover_letter,
            'status' => $application->status,
            'student_project_status' => $application->student_project_status,
            'student_project_note' => $application->student_project_note,
            'student_project_status_updated_at' => optional($application->student_project_status_updated_at)?->toISOString(),
            'progress_updates' => $application->progressUpdates
                ? $application->progressUpdates->map(
                    fn(ApplicationProgressUpdate $update) => $this->transformProgressUpdate($update)
                )->values()
                : [],
            'reviewed_at' => optional($application->reviewed_at)?->toISOString(),
            'created_at' => optional($application->created_at)?->toISOString(),
        ];
    }

    private function transformProgressUpdate(ApplicationProgressUpdate $update): array
    {
        return [
            'id' => $update->id,
            'application_id' => $update->application_id,
            'title' => $update->title,
            'notes' => $update->notes,
            'student_project_status' => $update->student_project_status,
            'created_at' => optional($update->created_at)?->toISOString(),
            'student' => [
                'id' => $update->student_user_id,
                'name' => $update->studentUser?->name,
            ],
        ];
    }
}
