<?php

namespace Tests\Feature;

use App\Models\StudentCvFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentCvUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'security.cv_antivirus_enabled' => false,
            'security.cv_antivirus_required' => false,
            'security.cv_antivirus_command' => 'clamscan --no-summary',
        ]);
    }

    public function test_student_can_upload_and_list_cv_files(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $file = UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf');

        $upload = $this->postJson('/api/profile/student/cv', [
            'cv' => $file,
        ]);

        $upload->assertCreated()->assertJsonStructure([
            'data' => ['id', 'original_filename', 'mime_type', 'size_bytes', 'scan_status', 'scan_message', 'scanned_at', 'uploaded_at', 'download_url'],
        ]);

        $list = $this->getJson('/api/profile/student/cv');
        $list->assertOk()->assertJsonCount(1, 'data');

        $this->assertDatabaseCount('student_cv_files', 1);
    }

    public function test_non_student_cannot_upload_cv(): void
    {
        Storage::fake('local');

        $company = User::factory()->create(['role' => 'company']);
        Sanctum::actingAs($company);

        $file = UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf');

        $response = $this->postJson('/api/profile/student/cv', [
            'cv' => $file,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('student_cv_files', 0);
    }

    public function test_student_can_delete_own_cv_file(): void
    {
        Storage::fake('local');

        $student = User::factory()->create(['role' => 'student']);
        Sanctum::actingAs($student);

        $cv = StudentCvFile::query()->create([
            'student_user_id' => $student->id,
            'storage_path' => 'private/cv/student/' . $student->id . '/resume.pdf',
            'original_filename' => 'resume.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 12345,
            'checksum_sha256' => hash('sha256', 'test'),
            'scan_status' => 'clean',
            'scan_message' => 'Antivirus scan passed.',
            'scanned_at' => now(),
            'uploaded_at' => now(),
        ]);

        Storage::disk('local')->put($cv->storage_path, 'dummy');

        $response = $this->deleteJson('/api/profile/student/cv/' . $cv->id);

        $response->assertOk();
        $this->assertDatabaseMissing('student_cv_files', ['id' => $cv->id]);
    }
}
