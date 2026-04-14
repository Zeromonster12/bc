<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 10;

        $projects = Project::query()
            ->with([
                'companyUser:id,name',
                'companyUser.companyProfile:id,user_id,logo_path',
            ])
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $projects->getCollection()->map(function (Project $project): array {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status,
                    'created_at' => optional($project->created_at)?->toISOString(),
                    'company' => [
                        'user_id' => $project->company_user_id,
                        'name' => $project->companyUser?->name,
                        'avatar_url' => $project->companyUser?->avatar_url,
                    ],
                ];
            })->values(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    public function destroy(int $projectId): JsonResponse
    {
        $project = Project::query()->find($projectId);

        if (! $project) {
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
            'project_id' => $projectId,
        ]);
    }
}
