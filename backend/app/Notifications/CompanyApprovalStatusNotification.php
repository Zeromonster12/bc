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

        return (new MailMessage)
            ->subject('Company account ' . $statusLabel)
            ->view('emails.company-approval-status', [
                'name' => (string) ($notifiable->name ?? ''),
                'approved' => $this->approved,
            ]);
    }
}
