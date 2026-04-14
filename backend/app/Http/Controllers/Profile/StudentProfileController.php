<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class StudentProfileController extends Controller
{
    private const AVATAR_DISK = 'userpfp';

    /**
     * @var array<int, string>
     */
    private const PROFILE_ALLOWED_FIELDS = [
        'headline',
        'date_of_birth',
        'gender',
        'phone',
        'alternate_email',
        'country',
        'city',
        'address_line',
        'postal_code',
        'university',
        'faculty',
        'degree',
        'field_of_study',
        'year_of_study',
        'graduation_year',
        'gpa',
        'bio',
        'about_me',
        'availability',
        'preferred_work_type',
        'preferred_locations',
        'expected_salary_min',
        'expected_salary_max',
        'skills',
        'interests',
        'portfolio_url',
        'cv_url',
        'github_url',
        'linkedin_url',
        'website_url',
        'languages',
        'certifications',
        'projects',
        'emergency_contact_name',
        'emergency_contact_phone',
        'consent_public_profile',
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can access student profile.',
            ], 403);
        }

        $profile = StudentProfile::query()->firstWhere('user_id', $user->id);

        return response()->json($this->transformProfile($profile, $user->id));
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

        return response()->json($this->transformProfile($profile->fresh(), $user->id));
    }

    public function showForCompany(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $actor) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($actor->role === 'company' && ! $this->companyCanAccessStudentProfile($actor->id, $user->id)) {
            return response()->json([
                'message' => 'You are not allowed to view this student profile.',
            ], 403);
        }

        if (! in_array($actor->role, ['company', 'admin'], true) && $actor->id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to view this student profile.',
            ], 403);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        $user->load([
            'studentProfile:id,user_id,profile_data,avatar_path',
        ]);

        $profileData = $this->normalizeProfileData(
            is_array($user->studentProfile?->profile_data)
                ? $user->studentProfile->profile_data
                : []
        );

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ],
                'profile' => $profileData,
            ],
        ]);
    }

    public function avatar(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can access profile photo.',
            ], 403);
        }

        $avatarPath = StudentProfile::query()
            ->where('user_id', $user->id)
            ->value('avatar_path');

        if (! is_string($avatarPath) || $avatarPath === '') {
            return response()->json([
                'message' => 'Profile photo not found.',
            ], 404);
        }

        return $this->streamAvatar($avatarPath);
    }

    public function userAvatar(Request $request, User $user)
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Profile photo not found.',
            ], 404);
        }

        $avatarPath = StudentProfile::query()
            ->where('user_id', $user->id)
            ->value('avatar_path');

        if (! is_string($avatarPath) || $avatarPath === '') {
            return response()->json([
                'message' => 'Profile photo not found.',
            ], 404);
        }

        return $this->streamAvatar($avatarPath);
    }

    public function signedUserAvatar(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired avatar URL.',
            ], 403);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Profile photo not found.',
            ], 404);
        }

        $avatarPath = StudentProfile::query()
            ->where('user_id', $user->id)
            ->value('avatar_path');

        if (! is_string($avatarPath) || $avatarPath === '') {
            return response()->json([
                'message' => 'Profile photo not found.',
            ], 404);
        }

        return $this->streamAvatar($avatarPath);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeProfileData(array $data): array
    {
        $normalized = [];

        foreach (self::PROFILE_ALLOWED_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $normalized[$field] = $data[$field];
        }

        if (array_key_exists('consent_public_profile', $normalized)) {
            $normalized['consent_public_profile'] = filter_var($normalized['consent_public_profile'], FILTER_VALIDATE_BOOL);
        }

        return $normalized;
    }

    private function companyCanAccessStudentProfile(int $companyUserId, int $studentUserId): bool
    {
        return Application::query()
            ->where('student_user_id', $studentUserId)
            ->whereHas('project', fn($query) => $query->where('company_user_id', $companyUserId))
            ->exists();
    }

    private function transformProfile(?StudentProfile $profile, int $userId): array
    {
        $data = $this->normalizeProfileData(
            is_array($profile?->profile_data)
                ? $profile->profile_data
                : []
        );

        if ($profile?->avatar_path) {
            $ttlMinutes = max(1, (int) config('filesystems.avatar_temporary_url_minutes', 60));
            $data['avatar_url'] = URL::temporarySignedRoute(
                'users.avatar.signed',
                now()->addMinutes($ttlMinutes),
                ['user' => $userId]
            );
        }

        return $data;
    }

    private function streamAvatar(string $avatarPath)
    {
        if (! Storage::disk(self::AVATAR_DISK)->exists($avatarPath)) {
            return response()->json([
                'message' => 'Profile photo not found in storage.',
            ], 404);
        }

        $stream = Storage::disk(self::AVATAR_DISK)->readStream($avatarPath);

        if (! is_resource($stream)) {
            return response()->json([
                'message' => 'Profile photo could not be streamed from storage.',
            ], 500);
        }

        $disk = Storage::disk(self::AVATAR_DISK);
        $mimeType = $disk instanceof FilesystemAdapter
            ? ($disk->mimeType($avatarPath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
