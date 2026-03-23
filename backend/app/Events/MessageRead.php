<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $readerUserId,
        public int $lastReadMessageId,
        public string $readAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.' . $this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'reader_user_id' => $this->readerUserId,
            'last_read_message_id' => $this->lastReadMessageId,
            'read_at' => $this->readAt,
        ];
    }
}
