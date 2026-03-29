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

    Route::middleware(['web', 'auth:sanctum'])->get('/user', function (Request $request) {
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
    // Shared authenticated endpoints (authorization still enforced in controllers where needed).
    Route::get('/conversation-users', [MessageController::class, 'searchableUsers']);
    Route::get('/conversations', [MessageController::class, 'indexConversations']);
    Route::post('/conversations', [MessageController::class, 'storeConversation']);
    Route::patch('/conversations/{conversation}', [MessageController::class, 'updateConversation']);
    Route::delete('/conversations/{conversation}', [MessageController::class, 'destroyConversation']);
    Route::post('/conversations/{conversation}/participants', [MessageController::class, 'addParticipant']);
    Route::delete('/conversations/{conversation}/participants/{participantUser}', [MessageController::class, 'removeParticipant']);
    Route::post('/conversations/{conversation}/participants/{participantUser}/promote-admin', [MessageController::class, 'promoteParticipantToAdmin']);
    Route::post('/conversations/{conversation}/participants/{participantUser}/demote-admin', [MessageController::class, 'demoteParticipantFromAdmin']);
    Route::get('/conversations/{conversation}', [MessageController::class, 'showConversation']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead']);
    Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}/tasks', [ApplicationController::class, 'listTasks']);
    Route::patch('/applications/{application}/tasks/{task}', [ApplicationController::class, 'updateTask']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    Route::get('/projects/{project}/task-board', [ProjectTaskBoardController::class, 'show']);
    Route::get('/projects/{project}/task-folders', [ProjectTaskBoardController::class, 'listFolders']);

    Route::get('/users/{user}/avatar', [StudentProfileController::class, 'userAvatar'])
        ->middleware('throttle:60,1')
        ->name('users.avatar.show');

    Route::get('/students/{user}/profile', [StudentProfileController::class, 'showForCompany'])
        ->middleware('throttle:30,1');

    Route::get('/companies/{user}/profile', [CompanyProfileController::class, 'showForViewer'])
        ->middleware('throttle:30,1');

    Route::get('/profile/student/cv/{cvFile}/download', [StudentCvController::class, 'download'])
        ->middleware('throttle:30,1')
        ->name('profile.student.cv.download');
});

Route::middleware(['auth:sanctum', 'role:student'])->group(function (): void {
    Route::post('/projects/{project}/apply', [ApplicationController::class, 'store']);

    Route::get('/profile/student', [StudentProfileController::class, 'show'])
        ->middleware('throttle:30,1');
    Route::get('/profile/student/avatar', [StudentProfileController::class, 'avatar'])
        ->middleware('throttle:60,1')
        ->name('profile.student.avatar.show');
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
    Route::delete('/profile/student/cv/{cvFile}', [StudentCvController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('profile.student.cv.destroy');
});

Route::middleware(['auth:sanctum', 'role:student,admin'])->group(function (): void {
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:company,admin'])->group(function (): void {
    Route::patch('/applications/{application}', [ApplicationController::class, 'update']);
    Route::post('/applications/{application}/tasks', [ApplicationController::class, 'storeTask']);
    Route::delete('/applications/{application}/tasks/{task}', [ApplicationController::class, 'destroyTask']);

    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    Route::post('/projects/{project}/task-folders', [ProjectTaskBoardController::class, 'storeFolder']);
    Route::patch('/projects/{project}/task-folders/{folder}', [ProjectTaskBoardController::class, 'updateFolder']);
    Route::delete('/projects/{project}/task-folders/{folder}', [ProjectTaskBoardController::class, 'destroyFolder']);
    Route::post('/projects/{project}/task-folders/{folder}/categories', [ProjectTaskBoardController::class, 'storeCategory']);
    Route::patch('/projects/{project}/task-folders/{folder}/categories/{category}', [ProjectTaskBoardController::class, 'updateCategory']);
    Route::delete('/projects/{project}/task-folders/{folder}/categories/{category}', [ProjectTaskBoardController::class, 'destroyCategory']);

    Route::get('/profile/company', [CompanyProfileController::class, 'show'])
        ->middleware('throttle:30,1');
    Route::put('/profile/company', [CompanyProfileController::class, 'update'])
        ->middleware('throttle:15,1');
});

Route::get('/users/{user}/avatar/signed', [StudentProfileController::class, 'signedUserAvatar'])
    ->middleware('throttle:120,1')
    ->name('users.avatar.signed');

Route::get('/users/{user}/company-logo/signed', [CompanyProfileController::class, 'signedUserLogo'])
    ->middleware('throttle:120,1')
    ->name('users.company-logo.signed');

Route::get('/conversations/{conversation}/avatar/signed', [MessageController::class, 'signedConversationAvatar'])
    ->middleware('throttle:120,1')
    ->name('conversations.avatar.signed');
