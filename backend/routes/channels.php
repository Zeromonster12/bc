<?php

use App\Models\ConversationParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:sanctum'],
]);

Broadcast::channel('conversations.{conversationId}', function ($user, int $conversationId): bool {
    return ConversationParticipant::query()
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();
});
