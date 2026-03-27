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

    public function showForViewer(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $actor) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! in_array($actor->role, ['student', 'company', 'admin'], true) && $actor->id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to view this company profile.',
            ], 403);
        }

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Company profile not found.',
            ], 404);
        }

        $profile = CompanyProfile::query()->firstWhere('user_id', $user->id);

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'profile' => $this->transformProfile($profile, $user->id),
            ],
        ]);
    }

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

        $incomingProfileData = $request->except(['logo']);

        $incomingName = trim((string) ($incomingProfileData['name'] ?? ''));
        $incomingBusinessName = trim((string) ($incomingProfileData['business_name'] ?? ''));
        $resolvedCompanyName = $incomingBusinessName !== '' ? $incomingBusinessName : $incomingName;
        if ($resolvedCompanyName !== '') {
            $incomingProfileData['name'] = $resolvedCompanyName;
            $incomingProfileData['business_name'] = $resolvedCompanyName;
        }

        $incomingBillingCity = trim((string) ($incomingProfileData['billing_city'] ?? ''));
        $incomingHeadquartersCity = trim((string) ($incomingProfileData['headquarters_city'] ?? ''));
        $resolvedCity = $incomingBillingCity !== '' ? $incomingBillingCity : $incomingHeadquartersCity;
        if ($resolvedCity !== '') {
            $incomingProfileData['billing_city'] = $resolvedCity;
            $incomingProfileData['headquarters_city'] = $resolvedCity;
        }

        $existingProfileData = is_array($profile->profile_data) ? $profile->profile_data : [];
        $profileData = array_merge($existingProfileData, $incomingProfileData);

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
