<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    private const AVATAR_DISK = 'userpfp';

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
            $previousAvatarPath = $avatarPath;

            try {
                $storedAvatarPath = $validated['avatar']->store('student-avatars/' . $user->id, self::AVATAR_DISK);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Failed to store profile photo.',
                    'detail' => (bool) config('app.debug', false) ? $e->getMessage() : null,
                ], 500);
            }

            if (! is_string($storedAvatarPath) || $storedAvatarPath === '') {
                report(new \RuntimeException('Storage write returned false for avatar upload on disk ' . self::AVATAR_DISK . '.'));

                return response()->json([
                    'message' => 'Failed to store profile photo.',
                    'detail' => (bool) config('app.debug', false)
                        ? 'Storage write returned false. Verify MinIO endpoint, credentials, bucket, and AWS_USE_PATH_STYLE_ENDPOINT.'
                        : null,
                ], 500);
            }

            $avatarPath = $storedAvatarPath;

            if (is_string($previousAvatarPath) && $previousAvatarPath !== '') {
                Storage::disk(self::AVATAR_DISK)->delete($previousAvatarPath);
            }
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
            $disk = Storage::disk(self::AVATAR_DISK);
            $data['avatar_url'] = $disk instanceof Cloud
                ? $disk->url($profile->avatar_path)
                : '/storage/' . $profile->avatar_path;
        }

        return $data;
    }
}
