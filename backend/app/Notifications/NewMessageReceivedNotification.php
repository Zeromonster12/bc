<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $messageId,
        private readonly int $senderUserId,
        private readonly string $senderName,
        private readonly string $messageBody,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(object $notifiable): array
    {
        $sender = trim($this->senderName) !== '' ? $this->senderName : 'New message';

        return [
            'kind' => 'message.received',
            'title' => 'New message',
            'body' => $sender . ': ' . Str::limit(trim($this->messageBody), 120),
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'sender_user_id' => $this->senderUserId,
            'sender_name' => $this->senderName,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'message.received';
    }
}
