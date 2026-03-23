<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function searchableUsers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        $limit = (int) ($validated['limit'] ?? 8);

        $users = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'data' => $users,
        ]);
    }

    public function indexConversations(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $conversations = Conversation::query()
            ->whereHas('participantRecords', fn($q) => $q->where('user_id', $user->id))
            ->with([
                'project:id,title',
                'participantRecords:id,conversation_id,user_id,last_read_message_id,last_read_at',
                'participantRecords.user:id,name,email',
                'messages:id,conversation_id,sender_user_id,body,created_at',
                'messages.senderUser:id,name,email',
            ])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'data' => $conversations->map(fn(Conversation $conversation) => $this->transformConversation($conversation, $user->id))->values(),
        ]);
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:recipient_email'],
            'recipient_email' => ['nullable', 'string', 'email', 'max:255', 'required_without:recipient_user_id'],
        ]);

        $recipient = null;
        $recipientUserId = isset($validated['recipient_user_id'])
            ? (int) $validated['recipient_user_id']
            : 0;

        if ($recipientUserId > 0) {
            $recipient = User::query()->find($recipientUserId);
        }

        if (! $recipient && isset($validated['recipient_email'])) {
            $recipient = User::query()->firstWhere('email', $validated['recipient_email']);
        }

        if (! $recipient) {
            return response()->json([
                'message' => 'Recipient not found.',
            ], 404);
        }

        if ($recipient->id === $user->id) {
            return response()->json([
                'message' => 'You cannot start a conversation with yourself.',
            ], 422);
        }

        $existing = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participantRecords', fn($q) => $q->where('user_id', $user->id))
            ->whereHas('participantRecords', fn($q) => $q->where('user_id', $recipient->id))
            ->with('participantRecords:id,conversation_id,user_id')
            ->get()
            ->first(fn(Conversation $conversation) => $conversation->participantRecords->count() === 2);

        if ($existing) {
            $existing->load([
                'project:id,title',
                'participantRecords:id,conversation_id,user_id,last_read_message_id,last_read_at',
                'participantRecords.user:id,name,email',
                'messages:id,conversation_id,sender_user_id,body,created_at',
                'messages.senderUser:id,name,email',
            ]);

            return response()->json([
                'data' => $this->transformConversation($existing, $user->id),
            ]);
        }

        $conversation = Conversation::query()->create([
            'type' => 'direct',
            'subject' => 'Direct chat',
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $recipient->id,
        ]);

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, $user->id),
        ], 201);
    }

    public function showConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->isParticipant($conversation, $user->id)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, $user->id),
        ]);
    }

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->isParticipant($conversation, $user->id)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $perPage = (int) $request->integer('per_page', 50);
        $perPage = max(1, min(100, $perPage));

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('senderUser:id,name,email')
            ->orderBy('id')
            ->paginate($perPage);

        $latestMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->latest('id')
            ->first();

        if ($latestMessage) {
            $this->syncReadPosition($conversation, $user->id, (int) $latestMessage->id);
        }

        $conversation->load([
            'participantRecords:id,conversation_id,user_id,last_read_message_id,last_read_at',
        ]);

        return response()->json([
            'data' => $messages->getCollection()->map(
                fn(Message $message) => $this->transformMessage($message, $conversation)
            )->values(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->isParticipant($conversation, $user->id)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $validated = $request->validate([
            'up_to_message_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $upToMessageId = (int) ($validated['up_to_message_id'] ?? 0);

        $targetMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->when(
                $upToMessageId > 0,
                fn($query) => $query->where('id', '<=', $upToMessageId)
            )
            ->latest('id')
            ->first();

        if (! $targetMessage) {
            return response()->json([
                'data' => [
                    'ok' => true,
                    'last_read_message_id' => null,
                ],
            ]);
        }

        $this->syncReadPosition($conversation, $user->id, (int) $targetMessage->id);

        return response()->json([
            'data' => [
                'ok' => true,
                'last_read_message_id' => (int) $targetMessage->id,
            ],
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->isParticipant($conversation, $user->id)) {
            return response()->json(['message' => 'You are not allowed to post in this conversation.'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => $user->id,
            'body' => trim((string) $validated['body']),
        ]);

        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update([
                'last_read_message_id' => $message->id,
                'last_read_at' => now(),
            ]);

        $conversation->update(['updated_at' => now()]);

        $message->load('senderUser:id,name,email');
        $conversation->load([
            'participantRecords:id,conversation_id,user_id,last_read_message_id,last_read_at',
        ]);

        broadcast(new MessageSent($conversation, $message))->toOthers();

        return response()->json([
            'data' => $this->transformMessage($message, $conversation),
        ], 201);
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->isParticipant($conversation, $user->id)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $validated = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        broadcast(new UserTyping($conversation, $user, (bool) $validated['is_typing']))->toOthers();

        return response()->json(['data' => ['ok' => true]]);
    }

    private function isParticipant(Conversation $conversation, int $userId): bool
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function transformConversation(Conversation $conversation, int $currentUserId): array
    {
        $participantRecords = $conversation->participantRecords;

        $lastMessage = $conversation->messages instanceof Collection
            ? $conversation->messages->sortByDesc('id')->first()
            : null;

        $lastReadMessageId = (int) ($participantRecords
            ? $participantRecords->firstWhere('user_id', $currentUserId)?->last_read_message_id
            : 0);

        $unreadCount = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_user_id', '!=', $currentUserId)
            ->when(
                $lastReadMessageId > 0,
                fn($q) => $q->where('id', '>', $lastReadMessageId)
            )
            ->count();

        return [
            'id' => $conversation->id,
            'subject' => $conversation->subject,
            'project' => $conversation->project
                ? [
                    'id' => $conversation->project->id,
                    'title' => $conversation->project->title,
                ]
                : null,
            'participants' => $participantRecords
                ? $participantRecords->map(fn(ConversationParticipant $participant) => [
                    'id' => $participant->user?->id,
                    'name' => $participant->user?->name,
                    'email' => $participant->user?->email,
                ])->values()
                : [],
            'last_message' => $lastMessage ? $this->transformMessage($lastMessage, $conversation) : null,
            'unread_count' => $unreadCount,
            'created_at' => optional($conversation->created_at)->toISOString(),
        ];
    }

    private function transformMessage(Message $message, Conversation $conversation): array
    {
        $readAt = null;

        $participantRecords = $conversation->participantRecords;
        if ($participantRecords) {
            $recipientRead = $participantRecords
                ->first(
                    fn(ConversationParticipant $participant) =>
                    $participant->user_id !== $message->sender_user_id
                        && (int) ($participant->last_read_message_id ?? 0) >= $message->id
                );

            if ($recipientRead?->last_read_at) {
                $readAt = $recipientRead->last_read_at->toISOString();
            }
        }

        return [
            'id' => $message->id,
            'sender' => [
                'id' => $message->senderUser?->id,
                'name' => $message->senderUser?->name,
                'email' => $message->senderUser?->email,
            ],
            'body' => $message->body,
            'read_at' => $readAt,
            'created_at' => optional($message->created_at)->toISOString(),
        ];
    }

    private function syncReadPosition(Conversation $conversation, int $readerUserId, int $lastReadMessageId): void
    {
        $participantRecord = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $readerUserId)
            ->first();

        $previousLastReadMessageId = (int) ($participantRecord?->last_read_message_id ?? 0);
        if ($lastReadMessageId <= $previousLastReadMessageId) {
            return;
        }

        $readAt = now();

        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $readerUserId)
            ->update([
                'last_read_message_id' => $lastReadMessageId,
                'last_read_at' => $readAt,
            ]);

        broadcast(new MessageRead(
            $conversation,
            $readerUserId,
            $lastReadMessageId,
            $readAt->toISOString(),
        ))->toOthers();
    }
}
