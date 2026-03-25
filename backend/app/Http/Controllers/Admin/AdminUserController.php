<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\CompanyApprovalStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:student,company,admin'],
            'company_status' => ['nullable', 'in:pending,approved,rejected'],
        ]);

        $users = User::query()
            ->when($validated['search'] ?? null, function ($query, $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($validated['role'] ?? null, function ($query, $role): void {
                $query->where('role', $role);
            })
            ->when($validated['company_status'] ?? null, function ($query, $companyStatus): void {
                $query->where('company_verification_status', $companyStatus);
            })
            ->latest('id')
            ->paginate(10);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,company,admin'],
        ]);

        $attributes = [
            'role' => $validated['role'],
        ];

        if ($validated['role'] === 'company' && $user->role !== 'company') {
            $attributes['company_verification_status'] = User::COMPANY_STATUS_PENDING;
            $attributes['company_verified_at'] = null;
        }

        if ($validated['role'] !== 'company') {
            $attributes['company_verification_status'] = User::COMPANY_STATUS_APPROVED;
            $attributes['company_verified_at'] = now();
        }

        $user->update($attributes);

        return response()->json([
            'data' => $user->fresh(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()?->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own admin account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function approveCompany(User $user): JsonResponse
    {
        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only company users can be approved.',
            ], 422);
        }

        $user->update([
            'company_verification_status' => User::COMPANY_STATUS_APPROVED,
            'company_verified_at' => now(),
        ]);

        try {
            $user->notify(new CompanyApprovalStatusNotification(true));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'data' => $user->fresh(),
        ]);
    }

    public function rejectCompany(User $user): JsonResponse
    {
        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only company users can be rejected.',
            ], 422);
        }

        $user->update([
            'company_verification_status' => User::COMPANY_STATUS_REJECTED,
            'company_verified_at' => null,
        ]);

        try {
            $user->notify(new CompanyApprovalStatusNotification(false));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'data' => $user->fresh(),
        ]);
    }
}
