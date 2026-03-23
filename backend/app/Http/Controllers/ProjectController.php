<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,open,closed'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 12;

        $projects = Project::query()
            ->with('companyUser:id,name')
            ->withCount('applications')
            ->when($validated['search'] ?? null, function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($validated['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($validated['company_id'] ?? null, fn($query, $companyId) => $query->where('company_user_id', $companyId))
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $projects->getCollection()->map(fn(Project $project) => $this->transformProject($project))->values(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load('companyUser:id,name')->loadCount('applications');

        return response()->json([
            'data' => $this->transformProject($project),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! in_array($request->user()?->role, ['company', 'admin'], true)) {
            return response()->json(['message' => 'Only company or admin users can create projects.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'requirements' => ['nullable', 'string'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'status' => ['required', 'in:draft,open,closed'],
            'max_students' => ['required', 'integer', 'min:1', 'max:100'],
            'deadline' => ['nullable', 'date'],
        ]);

        $project = Project::query()->create([
            ...$validated,
            'company_user_id' => $request->user()->id,
        ]);

        $project->load('companyUser:id,name')->loadCount('applications');

        return response()->json([
            'data' => $this->transformProject($project),
        ], 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user || ($user->role !== 'admin' && $project->company_user_id !== $user->id)) {
            return response()->json(['message' => 'You are not allowed to update this project.'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10'],
            'requirements' => ['nullable', 'string'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'status' => ['sometimes', 'in:draft,open,closed'],
            'max_students' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'deadline' => ['nullable', 'date'],
        ]);

        $project->update($validated);
        $project->load('companyUser:id,name')->loadCount('applications');

        return response()->json([
            'data' => $this->transformProject($project),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (! $user || ($user->role !== 'admin' && $project->company_user_id !== $user->id)) {
            return response()->json(['message' => 'You are not allowed to delete this project.'], 403);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    private function transformProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'company' => [
                'user_id' => $project->company_user_id,
                'name' => $project->companyUser?->name,
            ],
            'title' => $project->title,
            'description' => $project->description,
            'requirements' => $project->requirements,
            'tech_stack' => $project->tech_stack ?? [],
            'status' => $project->status,
            'max_students' => $project->max_students,
            'deadline' => optional($project->deadline)->format('Y-m-d'),
            'applications_count' => $project->applications_count ?? 0,
            'created_at' => optional($project->created_at)?->toISOString(),
        ];
    }
}
