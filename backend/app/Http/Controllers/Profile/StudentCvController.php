<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\StudentCvFile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StudentCvController extends Controller
{
    private const CV_DISK = 'usercv';

    /**
     * @var array<string, string>
     */
    private const MIME_TO_EXTENSION = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    private const DOWNLOADABLE_SCAN_STATUSES = ['clean', 'skipped'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can manage CV files.',
            ], 403);
        }

        $files = StudentCvFile::query()
            ->where('student_user_id', $user->id)
            ->latest('uploaded_at')
            ->get();

        return response()->json([
            'data' => $files->map(fn(StudentCvFile $file) => $this->transformFile($file))->values(),
        ]);
    }

    public function indexForStudent(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (! $actor) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        $projectId = $request->integer('project_id');
        $projectScope = $projectId > 0 ? $projectId : null;
        $isOwner = $actor->id === $user->id;
        $isAdmin = $actor->role === 'admin';
        $isLinkedCompany = $actor->role === 'company'
            && $this->companyCanAccessStudentCv($actor->id, $user->id, $projectScope);

        if (! $isOwner && ! $isAdmin && ! $isLinkedCompany) {
            return response()->json([
                'message' => 'You are not allowed to access CV files for this student.',
            ], 403);
        }

        $files = StudentCvFile::query()
            ->where('student_user_id', $user->id)
            ->whereIn('scan_status', self::DOWNLOADABLE_SCAN_STATUSES)
            ->latest('uploaded_at')
            ->get();

        return response()->json([
            'data' => $files->map(fn(StudentCvFile $file) => $this->transformFile($file))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can upload CV files.',
            ], 403);
        }

        $maxKb = (int) config('security.cv_upload_max_kb', 5120);

        $validated = $request->validate([
            'cv' => ['required', 'file', 'max:' . $maxKb, 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['cv'];
        $detectedMimeType = (string) ($file->getMimeType() ?: '');

        if (! array_key_exists($detectedMimeType, self::MIME_TO_EXTENSION)) {
            return response()->json([
                'message' => 'Unsupported CV format. Allowed formats: PDF, DOC, DOCX.',
            ], 422);
        }

        $extension = self::MIME_TO_EXTENSION[$detectedMimeType];
        $storagePath = sprintf('private/cv/student/%d/%s.%s', $user->id, (string) Str::uuid(), $extension);

        $scanResult = $this->scanUploadedFile($file->getRealPath());

        if ($scanResult['status'] === 'infected') {
            return response()->json([
                'message' => 'CV upload rejected: malware detected by antivirus scan.',
                'scan_detail' => Str::limit($scanResult['message'], 300, ''),
            ], 422);
        }

        $isScanRequired = (bool) config('security.cv_antivirus_required', false);
        if ($scanResult['status'] === 'scan_error' && $isScanRequired) {
            return response()->json([
                'message' => 'CV upload blocked: antivirus scan failed and scanning is required.',
            ], 503);
        }

        try {
            $stored = Storage::disk(self::CV_DISK)->putFileAs(
                dirname($storagePath),
                $file,
                basename($storagePath)
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to store CV file.',
                'detail' => (bool) config('app.debug', false) ? $e->getMessage() : null,
            ], 500);
        }

        if (! $stored) {
            report(new \RuntimeException('Storage write returned false for CV upload on disk ' . self::CV_DISK . '.'));

            return response()->json([
                'message' => 'Failed to store CV file.',
                'detail' => (bool) config('app.debug', false)
                    ? 'Storage write returned false. Verify MinIO endpoint, credentials, bucket, and AWS_USE_PATH_STYLE_ENDPOINT.'
                    : null,
            ], 500);
        }

        $tmpPath = $file->getRealPath();
        $checksum = $tmpPath ? hash_file('sha256', $tmpPath) : null;

        $record = StudentCvFile::query()->create([
            'student_user_id' => $user->id,
            'storage_path' => $storagePath,
            'original_filename' => Str::limit((string) $file->getClientOriginalName(), 255, ''),
            'mime_type' => $detectedMimeType,
            'size_bytes' => (int) $file->getSize(),
            'checksum_sha256' => $checksum ?: hash('sha256', (string) Str::uuid()),
            'scan_status' => $scanResult['status'],
            'scan_message' => Str::limit($scanResult['message'], 1000, ''),
            'scanned_at' => now(),
            'uploaded_at' => now(),
        ]);

        return response()->json([
            'data' => $this->transformFile($record),
            'message' => 'CV uploaded successfully.',
        ], 201);
    }

    public function download(Request $request, StudentCvFile $cvFile)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $projectId = $request->integer('project_id');
        $projectScope = $projectId > 0 ? $projectId : null;
        $isOwner = $cvFile->student_user_id === $user->id;
        $isAdmin = $user->role === 'admin';
        $isLinkedCompany = $user->role === 'company'
            && $this->companyCanAccessStudentCv($user->id, (int) $cvFile->student_user_id, $projectScope);

        if (! $isOwner && ! $isAdmin && ! $isLinkedCompany) {
            return response()->json([
                'message' => 'You are not allowed to access this CV file.',
            ], 403);
        }

        if (! Storage::disk(self::CV_DISK)->exists($cvFile->storage_path)) {
            return response()->json([
                'message' => 'CV file not found in storage.',
            ], 404);
        }

        if (! in_array($cvFile->scan_status, self::DOWNLOADABLE_SCAN_STATUSES, true)) {
            return response()->json([
                'message' => 'CV file is not available for download because scan status is not safe.',
            ], 423);
        }

        $stream = Storage::disk(self::CV_DISK)->readStream($cvFile->storage_path);

        if (! is_resource($stream)) {
            return response()->json([
                'message' => 'CV file could not be streamed from storage.',
            ], 500);
        }

        return response()->streamDownload(
            static function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            $cvFile->original_filename,
            [
                'Content-Type' => $cvFile->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }

    public function destroy(Request $request, StudentCvFile $cvFile): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'student') {
            return response()->json([
                'message' => 'Only students can delete CV files.',
            ], 403);
        }

        if ($cvFile->student_user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to delete this CV file.',
            ], 403);
        }

        Storage::disk(self::CV_DISK)->delete($cvFile->storage_path);
        $cvFile->delete();

        return response()->json([
            'message' => 'CV deleted successfully.',
        ]);
    }

    private function companyCanAccessStudentCv(int $companyUserId, int $studentUserId, ?int $projectId = null): bool
    {
        return Application::query()
            ->where('student_user_id', $studentUserId)
            ->whereIn('status', ['pending', 'accepted'])
            ->whereHas('project', function ($query) use ($companyUserId, $projectId): void {
                $query->where('company_user_id', $companyUserId);

                if ($projectId !== null) {
                    $query->where('id', $projectId);
                }
            })
            ->exists();
    }

    private function transformFile(StudentCvFile $file): array
    {
        return [
            'id' => $file->id,
            'original_filename' => $file->original_filename,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'scan_status' => $file->scan_status,
            'scan_message' => $file->scan_message,
            'scanned_at' => optional($file->scanned_at)?->toISOString(),
            'uploaded_at' => optional($file->uploaded_at)?->toISOString(),
            'download_url' => route('profile.student.cv.download', ['cvFile' => $file->id]),
        ];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function scanUploadedFile(?string $fullPath): array
    {
        $antivirusEnabled = (bool) config('security.cv_antivirus_enabled', false);

        if (! $antivirusEnabled) {
            return [
                'status' => 'skipped',
                'message' => 'Antivirus scanning disabled by configuration.',
            ];
        }

        if (! is_string($fullPath) || $fullPath === '' || ! is_file($fullPath) || ! is_readable($fullPath)) {
            return [
                'status' => 'scan_error',
                'message' => 'Uploaded file is not readable for antivirus scan.',
            ];
        }

        $driver = trim((string) config('security.cv_antivirus_driver', 'command'));

        if ($driver === 'clamd_tcp') {
            return $this->scanWithClamdTcp($fullPath);
        }

        return $this->scanWithCommand($fullPath);
    }

    /**
     * @return array{status: string, message: string}
     */
    private function scanWithCommand(string $fullPath): array
    {
        $command = trim((string) config('security.cv_antivirus_command', 'clamscan --no-summary'));

        if ($command === '') {
            return [
                'status' => 'scan_error',
                'message' => 'Antivirus command is empty.',
            ];
        }

        try {
            $normalizedCommand = str_replace('\\"', '"', $command);

            $commandParts = str_getcsv($normalizedCommand, ' ', '"');
            $commandParts = array_values(array_filter(
                array_map(
                    static fn($part) => is_string($part) ? trim($part, " \t\n\r\0\x0B\"'") : $part,
                    $commandParts
                ),
                fn($part) => is_string($part) && trim($part) !== ''
            ));

            if ($commandParts === []) {
                return [
                    'status' => 'scan_error',
                    'message' => 'Antivirus command has no executable.',
                ];
            }

            $result = Process::timeout(30)->run([...$commandParts, $fullPath]);

            if ($result->successful()) {
                return [
                    'status' => 'clean',
                    'message' => 'Antivirus scan passed.',
                ];
            }

            if ($result->exitCode() === 1) {
                return [
                    'status' => 'infected',
                    'message' => trim($result->errorOutput() ?: $result->output()) ?: 'Antivirus detected infected file.',
                ];
            }

            return [
                'status' => 'scan_error',
                'message' => trim($result->errorOutput() ?: $result->output()) ?: 'Antivirus scan command failed.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'scan_error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function scanWithClamdTcp(string $fullPath): array
    {
        $host = trim((string) config('security.cv_antivirus_clamd_host', 'clamav'));
        $port = (int) config('security.cv_antivirus_clamd_port', 3310);
        $timeout = (float) config('security.cv_antivirus_clamd_timeout', 10);

        if ($host === '' || $port < 1 || $port > 65535) {
            return [
                'status' => 'scan_error',
                'message' => 'Invalid clamd host or port configuration.',
            ];
        }

        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            return [
                'status' => 'scan_error',
                'message' => 'Stored file is not readable for antivirus scan.',
            ];
        }

        $errno = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $host, $port),
            $errno,
            $errstr,
            $timeout
        );

        if (! is_resource($socket)) {
            return [
                'status' => 'scan_error',
                'message' => sprintf('Failed to connect to clamd at %s:%d (%s).', $host, $port, $errstr !== '' ? $errstr : 'connection failed'),
            ];
        }

        try {
            stream_set_timeout($socket, (int) max(1, ceil($timeout)));

            if (fwrite($socket, "zINSTREAM\0") === false) {
                return [
                    'status' => 'scan_error',
                    'message' => 'Failed to initialize clamd INSTREAM command.',
                ];
            }

            $fileHandle = fopen($fullPath, 'rb');
            if (! is_resource($fileHandle)) {
                return [
                    'status' => 'scan_error',
                    'message' => 'Failed to open file for clamd scan.',
                ];
            }

            try {
                while (! feof($fileHandle)) {
                    $chunk = fread($fileHandle, 8192);
                    if ($chunk === false) {
                        return [
                            'status' => 'scan_error',
                            'message' => 'Failed while reading file for antivirus scan.',
                        ];
                    }

                    if ($chunk === '') {
                        continue;
                    }

                    $packet = pack('N', strlen($chunk)) . $chunk;
                    if (fwrite($socket, $packet) === false) {
                        return [
                            'status' => 'scan_error',
                            'message' => 'Failed while streaming file to clamd.',
                        ];
                    }
                }
            } finally {
                fclose($fileHandle);
            }

            if (fwrite($socket, pack('N', 0)) === false) {
                return [
                    'status' => 'scan_error',
                    'message' => 'Failed to finalize clamd stream.',
                ];
            }

            $response = trim((string) stream_get_contents($socket));

            if ($response === '') {
                return [
                    'status' => 'scan_error',
                    'message' => 'No response from clamd.',
                ];
            }

            if (str_contains($response, 'FOUND')) {
                return [
                    'status' => 'infected',
                    'message' => $response,
                ];
            }

            if (str_contains($response, 'OK')) {
                return [
                    'status' => 'clean',
                    'message' => 'Antivirus scan passed.',
                ];
            }

            return [
                'status' => 'scan_error',
                'message' => $response,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'scan_error',
                'message' => $e->getMessage(),
            ];
        } finally {
            fclose($socket);
        }
    }
}
