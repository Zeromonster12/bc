<?php

use App\Http\Controllers\Admin\AdminProjectController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProjectTaskBoardController;
use App\Http\Controllers\Profile\CompanyProfileController;
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
    Route::patch('/users/{user}/approve-company', [AdminUserController::class, 'approveCompany']);
    Route::patch('/users/{user}/reject-company', [AdminUserController::class, 'rejectCompany']);
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
    Route::get('/applications/{application}/tasks', [ApplicationController::class, 'listTasks']);
    Route::post('/applications/{application}/tasks', [ApplicationController::class, 'storeTask']);
    Route::patch('/applications/{application}/tasks/{task}', [ApplicationController::class, 'updateTask']);
    Route::delete('/applications/{application}/tasks/{task}', [ApplicationController::class, 'destroyTask']);
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    Route::get('/projects/{project}/task-board', [ProjectTaskBoardController::class, 'show']);
    Route::get('/projects/{project}/task-folders', [ProjectTaskBoardController::class, 'listFolders']);
    Route::post('/projects/{project}/task-folders', [ProjectTaskBoardController::class, 'storeFolder']);
    Route::patch('/projects/{project}/task-folders/{folder}', [ProjectTaskBoardController::class, 'updateFolder']);
    Route::delete('/projects/{project}/task-folders/{folder}', [ProjectTaskBoardController::class, 'destroyFolder']);
    Route::post('/projects/{project}/task-folders/{folder}/categories', [ProjectTaskBoardController::class, 'storeCategory']);
    Route::patch('/projects/{project}/task-folders/{folder}/categories/{category}', [ProjectTaskBoardController::class, 'updateCategory']);
    Route::delete('/projects/{project}/task-folders/{folder}/categories/{category}', [ProjectTaskBoardController::class, 'destroyCategory']);

    Route::get('/profile/student', [StudentProfileController::class, 'show'])
        ->middleware('throttle:30,1');
    Route::get('/profile/student/avatar', [StudentProfileController::class, 'avatar'])
        ->middleware('throttle:60,1')
        ->name('profile.student.avatar.show');
    Route::get('/users/{user}/avatar', [StudentProfileController::class, 'userAvatar'])
        ->middleware('throttle:60,1')
        ->name('users.avatar.show');
    Route::post('/profile/student', [StudentProfileController::class, 'update'])
        ->middleware('throttle:15,1');
    Route::put('/profile/student', [StudentProfileController::class, 'update'])
        ->middleware('throttle:15,1');

    Route::get('/profile/company', [CompanyProfileController::class, 'show'])
        ->middleware('throttle:30,1');
    Route::put('/profile/company', [CompanyProfileController::class, 'update'])
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

Route::get('/users/{user}/avatar/signed', [StudentProfileController::class, 'signedUserAvatar'])
    ->middleware('throttle:120,1')
    ->name('users.avatar.signed');

Route::get('/users/{user}/company-logo/signed', [CompanyProfileController::class, 'signedUserLogo'])
    ->middleware('throttle:120,1')
    ->name('users.company-logo.signed');
