<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyApprovalStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly bool $approved) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->approved ? 'approved' : 'rejected';

        $mail = (new MailMessage)
            ->subject('Company account ' . $statusLabel)
            ->greeting('Hello ' . $notifiable->name . ',');

        if ($this->approved) {
            return $mail
                ->line('Your company account has been approved by an administrator.')
                ->line('You can now create and manage projects on the platform.');
        }

        return $mail
            ->line('Your company account has been reviewed but was not approved yet.')
            ->line('Please complete your company profile details and contact support if you believe this is a mistake.');
    }
}
