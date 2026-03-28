<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.' . $this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message' => [
                'id' => $this->message->id,
                'sender' => [
                    'id' => $this->message->senderUser?->id,
                    'name' => $this->message->senderUser?->name,
                    'email' => $this->message->senderUser?->email,
                    'avatar_url' => $this->message->senderUser?->avatar_url,
                ],
                'body' => $this->message->body,
                'read_at' => null,
                'created_at' => optional($this->message->created_at)->toISOString(),
            ],
        ];
    }
}
