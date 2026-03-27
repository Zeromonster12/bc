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
            'location' => ['nullable', 'string', 'max:255'],
            'sort_date' => ['nullable', 'in:newest,oldest'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 12;

        $projects = Project::query()
            ->with([
                'companyUser:id,name',
                'companyUser.companyProfile:id,user_id,profile_data',
            ])
            ->withCount('applications')
            ->withCount([
                'applications as accepted_applications_count' => fn($query) => $query->where('status', 'accepted'),
            ])
            ->when($validated['search'] ?? null, function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($validated['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when(! array_key_exists('status', $validated), fn($query) => $query->whereIn('status', ['open', 'closed']))
            ->when($validated['location'] ?? null, function ($query, $location): void {
                $query->where('location', 'like', '%' . $location . '%');
            })
            ->when($validated['tech_stack'] ?? null, function ($query, $techStack): void {
                $query->where(function ($inner) use ($techStack): void {
                    foreach ($techStack as $technology) {
                        $inner->orWhereJsonContains('tech_stack', $technology);
                    }
                });
            })
            ->when($validated['company_id'] ?? null, fn($query, $companyId) => $query->where('company_user_id', $companyId))
            ->when(
                ($validated['sort_date'] ?? 'newest') === 'oldest',
                fn($query) => $query->oldest('created_at'),
                fn($query) => $query->latest('created_at')
            )
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
        $project->load([
            'companyUser:id,name',
            'companyUser.companyProfile:id,user_id,profile_data',
        ])->loadCount([
            'applications',
            'applications as accepted_applications_count' => fn($query) => $query->where('status', 'accepted'),
        ]);

        return response()->json([
            'data' => $this->transformProject($project),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['company', 'admin'], true)) {
            return response()->json(['message' => 'Only company or admin users can create projects.'], 403);
        }

        if ($user?->role === 'company' && ! $user->isCompanyVerified()) {
            return response()->json([
                'message' => 'Your company account is pending admin approval. You can create projects after approval.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'requirements' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_strategy' => ['required', 'in:remote,onsite,hybrid'],
            'industry' => ['required', 'string', 'max:120'],
            'internship_duration' => ['required', 'string', 'max:120'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'status' => ['required', 'in:draft,open,closed'],
            'max_students' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $project = Project::query()->create([
            ...$validated,
            'company_user_id' => $request->user()->id,
        ]);

        $project->load([
            'companyUser:id,name',
            'companyUser.companyProfile:id,user_id,profile_data',
        ])->loadCount([
            'applications',
            'applications as accepted_applications_count' => fn($query) => $query->where('status', 'accepted'),
        ]);

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

        if ($user->role === 'company' && ! $user->isCompanyVerified()) {
            return response()->json([
                'message' => 'Your company account is pending admin approval. You can update projects after approval.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10'],
            'requirements' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'location_strategy' => ['sometimes', 'in:remote,onsite,hybrid'],
            'industry' => ['sometimes', 'string', 'max:120'],
            'internship_duration' => ['sometimes', 'string', 'max:120'],
            'tech_stack' => ['nullable', 'array'],
            'tech_stack.*' => ['string', 'max:50'],
            'status' => ['sometimes', 'in:draft,open,closed'],
            'max_students' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $project->update($validated);
        $project->load([
            'companyUser:id,name',
            'companyUser.companyProfile:id,user_id,profile_data',
        ])->loadCount([
            'applications',
            'applications as accepted_applications_count' => fn($query) => $query->where('status', 'accepted'),
        ]);

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
        $companyProfileData = is_array($project->companyUser?->companyProfile?->profile_data)
            ? $project->companyUser->companyProfile->profile_data
            : [];
        $companyName = trim((string) ($companyProfileData['business_name']
            ?? $companyProfileData['name']
            ?? $project->companyUser?->name
            ?? ''));

        return [
            'id' => $project->id,
            'company' => [
                'user_id' => $project->company_user_id,
                'name' => $companyName !== '' ? $companyName : null,
            ],
            'title' => $project->title,
            'description' => $project->description,
            'requirements' => $project->requirements,
            'location' => $project->location,
            'location_strategy' => $project->location_strategy ?? 'remote',
            'industry' => $project->industry,
            'internship_duration' => $project->internship_duration,
            'tech_stack' => $project->tech_stack ?? [],
            'status' => $project->status,
            'max_students' => $project->max_students,
            'posted_at' => optional($project->created_at)?->toISOString(),
            'applications_count' => $project->applications_count ?? 0,
            'accepted_applications_count' => $project->accepted_applications_count ?? 0,
            'created_at' => optional($project->created_at)?->toISOString(),
        ];
    }
}
