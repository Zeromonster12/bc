<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyApprovalStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly bool $approved,
        private readonly ?string $changedAt = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
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

    /**
     * @return array<string, int|string>
     */
    public function toArray(object $notifiable): array
    {
        $status = $this->approved ? 'approved' : 'rejected';

        return [
            'kind' => $this->broadcastType(),
            'title' => $this->approved ? 'Company account approved' : 'Company account rejected',
            'body' => $this->approved
                ? 'Your company account was approved. You can now publish projects.'
                : 'Your company account was rejected. Please review your company profile details.',
            'user_id' => (int) ($notifiable->id ?? 0),
            'company_verification_status' => $status,
            'changed_at' => $this->changedAt ?? now()->toIso8601String(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return $this->approved ? 'company.approved' : 'company.rejected';
    }
}
