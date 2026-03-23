<?php

use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Profile\StudentCvController;
use App\Http\Controllers\Profile\StudentProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    require base_path('routes/auth.php');

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user(),
        ]);
    });
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::patch('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

    Route::get('/projects', [AdminProjectController::class, 'index']);
    Route::delete('/projects/{projectId}', [AdminProjectController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/conversation-users', [MessageController::class, 'searchableUsers']);
    Route::get('/conversations', [MessageController::class, 'indexConversations']);
    Route::post('/conversations', [MessageController::class, 'storeConversation']);
    Route::get('/conversations/{conversation}', [MessageController::class, 'showConversation']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead']);
    Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/projects/{project}/apply', [ApplicationController::class, 'store']);
    Route::patch('/applications/{application}', [ApplicationController::class, 'update']);
    Route::patch('/applications/{application}/student-progress', [ApplicationController::class, 'updateStudentProgress']);
    Route::get('/applications/{application}/progress-updates', [ApplicationController::class, 'listProgressUpdates']);
    Route::post('/applications/{application}/progress-updates', [ApplicationController::class, 'storeProgressUpdate']);
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    Route::get('/profile/student', [StudentProfileController::class, 'show'])
        ->middleware('throttle:30,1');
    Route::post('/profile/student', [StudentProfileController::class, 'update'])
        ->middleware('throttle:15,1');
    Route::put('/profile/student', [StudentProfileController::class, 'update'])
        ->middleware('throttle:15,1');

    Route::get('/profile/student/cv', [StudentCvController::class, 'index'])
        ->middleware('throttle:30,1')
        ->name('profile.student.cv.index');
    Route::post('/profile/student/cv', [StudentCvController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('profile.student.cv.store');
    Route::get('/profile/student/cv/{cvFile}/download', [StudentCvController::class, 'download'])
        ->middleware('throttle:30,1')
        ->name('profile.student.cv.download');
    Route::delete('/profile/student/cv/{cvFile}', [StudentCvController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('profile.student.cv.destroy');
});
