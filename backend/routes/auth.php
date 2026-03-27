<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\GitHubConnectionController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/google/redirect', [GoogleOAuthController::class, 'redirect'])
    ->middleware('throttle:20,1')
    ->name('oauth.google.redirect');

Route::get('/google/callback', [GoogleOAuthController::class, 'callback'])
    ->middleware('throttle:20,1')
    ->name('oauth.google.callback');

Route::middleware(['auth:sanctum', 'role:student'])->group(function (): void {
    Route::get('/github/connect/status', [GitHubConnectionController::class, 'status'])
        ->middleware('throttle:30,1')
        ->name('oauth.github.connect.status');

    Route::get('/github/connect/redirect', [GitHubConnectionController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('oauth.github.connect.redirect');

    Route::get('/github/connect/callback', [GitHubConnectionController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('oauth.github.connect.callback');

    Route::get('/github/connect/insights', [GitHubConnectionController::class, 'insights'])
        ->middleware('throttle:20,1')
        ->name('oauth.github.connect.insights');

    Route::delete('/github/connect', [GitHubConnectionController::class, 'disconnect'])
        ->middleware('throttle:30,1')
        ->name('oauth.github.connect.disconnect');
});

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('login');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.store');

Route::post('/verify-email-code', [EmailVerificationCodeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('verification.code.verify');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');
