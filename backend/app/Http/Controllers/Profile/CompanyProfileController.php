<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CompanyProfileController extends Controller
{
    private const LOGO_DISK = 'companyavatar';

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can access company profile.',
            ], 403);
        }

        $profile = CompanyProfile::query()->firstWhere('user_id', $user->id);

        return response()->json($this->transformProfile($profile, $user->id));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can update company profile.',
            ], 403);
        }

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $profile = CompanyProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['profile_data' => []]
        );

        $profileData = $request->except(['logo']);

        $logoPath = $profile->logo_path;
        if (array_key_exists('logo', $validated) && $validated['logo']) {
            $previousLogoPath = $logoPath;

            try {
                $storedLogoPath = $validated['logo']->store('company-avatars/' . $user->id, self::LOGO_DISK);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Failed to store company logo.',
                    'detail' => (bool) config('app.debug', false) ? $e->getMessage() : null,
                ], 500);
            }

            if (! is_string($storedLogoPath) || $storedLogoPath === '') {
                report(new \RuntimeException('Storage write returned false for logo upload on disk ' . self::LOGO_DISK . '.'));

                return response()->json([
                    'message' => 'Failed to store company logo.',
                    'detail' => (bool) config('app.debug', false)
                        ? 'Storage write returned false. Verify MinIO endpoint, credentials, bucket, and AWS_USE_PATH_STYLE_ENDPOINT.'
                        : null,
                ], 500);
            }

            $logoPath = $storedLogoPath;

            if (is_string($previousLogoPath) && $previousLogoPath !== '') {
                Storage::disk(self::LOGO_DISK)->delete($previousLogoPath);
            }
        }

        $profile->update([
            'profile_data' => $profileData,
            'logo_path' => $logoPath,
        ]);

        return response()->json($this->transformProfile($profile->fresh(), $user->id));
    }

    public function signedUserLogo(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired logo URL.',
            ], 403);
        }

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Company logo not found.',
            ], 404);
        }

        $logoPath = CompanyProfile::query()
            ->where('user_id', $user->id)
            ->value('logo_path');

        if (! is_string($logoPath) || $logoPath === '') {
            return response()->json([
                'message' => 'Company logo not found.',
            ], 404);
        }

        return $this->streamLogo($logoPath);
    }

    private function transformProfile(?CompanyProfile $profile, int $userId): array
    {
        $data = is_array($profile?->profile_data) ? $profile->profile_data : [];

        if ($profile?->logo_path) {
            $ttlMinutes = max(1, (int) config('filesystems.avatar_temporary_url_minutes', 60));
            $data['logo_url'] = URL::temporarySignedRoute(
                'users.company-logo.signed',
                now()->addMinutes($ttlMinutes),
                ['user' => $userId]
            );
        }

        return $data;
    }

    private function streamLogo(string $logoPath)
    {
        if (! Storage::disk(self::LOGO_DISK)->exists($logoPath)) {
            return response()->json([
                'message' => 'Company logo not found in storage.',
            ], 404);
        }

        $stream = Storage::disk(self::LOGO_DISK)->readStream($logoPath);

        if (! is_resource($stream)) {
            return response()->json([
                'message' => 'Company logo could not be streamed from storage.',
            ], 500);
        }

        $disk = Storage::disk(self::LOGO_DISK);
        $mimeType = $disk instanceof FilesystemAdapter
            ? ($disk->mimeType($logoPath) ?: 'application/octet-stream')
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
