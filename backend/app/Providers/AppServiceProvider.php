<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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
