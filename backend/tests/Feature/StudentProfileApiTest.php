<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO6fWl8AAAAASUVORK5CYII=';

    public function test_student_profile_can_be_created_and_fetched(): void
    {
        Storage::fake('public');

        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $response = $this->put('/api/profile/student', [
            'headline' => 'Junior Backend Developer',
            'bio' => 'I build APIs.',
            'github_url' => 'https://github.com/octocat',
            'consent_public_profile' => '1',
            'avatar' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode(self::TINY_PNG_BASE64)
            ),
        ]);

        $response->assertOk()->assertJson([
            'headline' => 'Junior Backend Developer',
            'bio' => 'I build APIs.',
            'github_url' => 'https://github.com/octocat',
            'consent_public_profile' => true,
        ]);

        $fetch = $this->getJson('/api/profile/student');
        $fetch->assertOk()->assertJson([
            'headline' => 'Junior Backend Developer',
            'bio' => 'I build APIs.',
            'github_url' => 'https://github.com/octocat',
            'consent_public_profile' => true,
        ]);
        $fetch->assertJsonStructure(['avatar_url']);
    }

    public function test_student_profile_avatar_can_be_uploaded_via_post_multipart(): void
    {
        Storage::fake('public');

        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $response = $this->post('/api/profile/student', [
            '_method' => 'PUT',
            'headline' => 'Profile with avatar',
            'avatar' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode(self::TINY_PNG_BASE64)
            ),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['avatar_url']);
        $avatarUrl = (string) $response->json('avatar_url', '');
        $this->assertStringStartsWith('/storage/student-avatars/' . $student->id . '/', $avatarUrl);

        $storedFiles = Storage::disk('public')->allFiles('student-avatars/' . $student->id);
        $this->assertNotEmpty($storedFiles);
    }

    public function test_company_cannot_update_student_profile(): void
    {
        $company = User::factory()->create(['role' => 'company']);
        Sanctum::actingAs($company);

        $response = $this->putJson('/api/profile/student', [
            'headline' => 'Should fail',
        ]);

        $response->assertForbidden();
    }
}
