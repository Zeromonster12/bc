<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('chat-write', function (Request $request) {
            $userId = (int) ($request->user()?->id ?? 0);

            return Limit::perMinute(30)->by($userId > 0 ? 'chat-write:user:' . $userId : 'chat-write:ip:' . $request->ip());
        });

        RateLimiter::for('chat-read', function (Request $request) {
            $userId = (int) ($request->user()?->id ?? 0);

            return Limit::perMinute(240)->by($userId > 0 ? 'chat-read:user:' . $userId : 'chat-read:ip:' . $request->ip());
        });

        RateLimiter::for('chat-typing', function (Request $request) {
            $userId = (int) ($request->user()?->id ?? 0);

            return Limit::perMinute(180)->by($userId > 0 ? 'chat-typing:user:' . $userId : 'chat-typing:ip:' . $request->ip());
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $resetUrl = config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";

            return (new MailMessage)
                ->subject('Project Linker - Reset Password')
                ->view('emails.password-reset', [
                    'name' => (string) ($notifiable->name ?? ''),
                    'resetUrl' => $resetUrl,
                ]);
        });
    }
}
