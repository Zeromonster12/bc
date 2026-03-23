<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can access student profile.',
            ], 403);
        }

        $profile = StudentProfile::query()->firstWhere('user_id', $user->id);

        return response()->json($this->transformProfile($profile));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can update student profile.',
            ], 403);
        }

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $profile = StudentProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['profile_data' => []]
        );

        $profileData = $this->normalizeProfileData($request->except(['avatar']));

        $avatarPath = $profile->avatar_path;
        if (array_key_exists('avatar', $validated) && $validated['avatar']) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }

            $avatarPath = $validated['avatar']->store('student-avatars/' . $user->id, 'public');
        }

        $profile->update([
            'profile_data' => $profileData,
            'avatar_path' => $avatarPath,
        ]);

        return response()->json($this->transformProfile($profile->fresh()));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeProfileData(array $data): array
    {
        if (array_key_exists('consent_public_profile', $data)) {
            $data['consent_public_profile'] = filter_var($data['consent_public_profile'], FILTER_VALIDATE_BOOL);
        }

        return $data;
    }

    private function transformProfile(?StudentProfile $profile): array
    {
        $data = is_array($profile?->profile_data) ? $profile->profile_data : [];

        if ($profile?->avatar_path) {
            $data['avatar_url'] = '/storage/' . $profile->avatar_path;
        }

        return $data;
    }
}
