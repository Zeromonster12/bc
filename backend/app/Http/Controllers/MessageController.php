<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\UserTyping;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class MessageController extends Controller
{
    private const GROUP_AVATAR_DISK = 'groupavatar';

    public function searchableUsers(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 8);
        $projectId = isset($validated['project_id']) ? (int) $validated['project_id'] : 0;
        $allowedRecipientIds = $user->role === 'admin'
            ? $this->allowedDirectRecipientIds($user)
            : ($projectId > 0
                ? $this->allowedGroupParticipantIdsForProject($user, $projectId)
                : $this->allowedDirectRecipientIds($user));

        if ($projectId <= 0 && $query !== '' && mb_strlen($query) < 2) {
            return response()->json([
                'data' => [],
            ]);
        }

        if ($allowedRecipientIds === []) {
            return response()->json([
                'data' => [],
            ]);
        }

        $users = User::query()
            ->where('id', '!=', $user->id)
            ->whereIn('id', $allowedRecipientIds)
            ->when($query !== '', function ($queryBuilder) use ($query): void {
                $queryBuilder->where(function ($q) use ($query): void {
                    $q->where('name', 'like', '%' . $query . '%')
                        ->orWhere('email', 'like', '%' . $query . '%');
                });
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
                'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
                'participantRecords.user:id,name,email,role',
                'messages:id,conversation_id,sender_user_id,body,created_at',
                'messages.senderUser:id,name,email,role',
            ])
            ->latest('updated_at')
            ->get()
            ->filter(fn(Conversation $conversation) => $this->canUseConversation($user, $conversation))
            ->values();

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
            'type' => ['nullable', 'in:direct,group'],
            'subject' => ['nullable', 'string', 'min:3', 'max:160'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'participant_user_ids' => ['nullable', 'array', 'min:1'],
            'participant_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'recipient_user_id' => ['exclude_if:type,group', 'nullable', 'integer', 'exists:users,id', 'required_without:recipient_email'],
            'recipient_email' => ['exclude_if:type,group', 'nullable', 'string', 'email', 'max:255', 'required_without:recipient_user_id'],
        ]);

        $conversationType = (string) ($validated['type'] ?? 'direct');
        if ($conversationType === 'group') {
            return $this->storeGroupConversation($user, $validated);
        }

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

        if (! $this->canDirectMessage($user, $recipient)) {
            return response()->json([
                'message' => 'You are not allowed to start a conversation with this user.',
            ], 403);
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
                'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
                'participantRecords.user:id,name,email,role',
                'messages:id,conversation_id,sender_user_id,body,created_at',
                'messages.senderUser:id,name,email,role',
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
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, $user->id),
        ], 201);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function storeGroupConversation(User $user, array $validated): JsonResponse
    {
        $projectId = (int) ($validated['project_id'] ?? 0);
        $isAdmin = $user->role === 'admin';
        $project = null;

        if ($projectId > 0) {
            $project = Project::query()->find($projectId);
            if (! $project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }
        }

        if (! $isAdmin && $projectId <= 0) {
            return response()->json([
                'message' => 'Project is required for group conversation.',
            ], 422);
        }

        $allowedParticipantIds = $isAdmin
            ? $this->allowedDirectRecipientIds($user)
            : $this->allowedGroupParticipantIdsForProject($user, $projectId);

        if (! $isAdmin && $allowedParticipantIds === []) {
            return response()->json([
                'message' => 'You are not allowed to create a group chat for this project.',
            ], 403);
        }

        $selectedParticipantIds = collect($validated['participant_user_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn(int $id) => $id > 0 && $id !== (int) $user->id)
            ->unique()
            ->values()
            ->all();

        $invalidParticipantIds = array_values(array_diff($selectedParticipantIds, $allowedParticipantIds));
        if ($invalidParticipantIds !== []) {
            return response()->json([
                'message' => 'One or more selected participants are not allowed for this project.',
            ], 422);
        }

        $finalParticipantIds = collect($selectedParticipantIds)
            ->push((int) $user->id)
            ->when(
                ! $isAdmin && $project,
                fn($collection) => $collection->push((int) $project->company_user_id)
            )
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($finalParticipantIds) < 2) {
            return response()->json([
                'message' => 'Group conversation must contain at least 2 participants.',
            ], 422);
        }

        $subject = trim((string) ($validated['subject'] ?? ''));
        if ($subject === '') {
            $subject = trim((string) ($project?->title ?? ($isAdmin ? 'Admin Group Chat' : 'Project Group Chat')));
        }

        $conversation = Conversation::query()->create([
            'type' => 'group',
            'subject' => $subject,
            'project_id' => $project?->id,
        ]);

        foreach ($finalParticipantIds as $participantId) {
            ConversationParticipant::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $participantId,
                'is_admin' => (int) $participantId === (int) $user->id,
            ]);
        }

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, $user->id),
        ], 201);
    }

    public function addParticipant(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageGroupParticipants($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to manage this group chat.'], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Participants can be managed only on project group chats.'], 422);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetUserId = (int) $validated['user_id'];

        $allowedParticipantIds = (int) ($conversation->project_id ?? 0) > 0
            ? $this->allowedGroupParticipantIdsForProject($user, (int) $conversation->project_id)
            : $this->allowedDirectRecipientIds($user);
        if (! in_array($targetUserId, $allowedParticipantIds, true)) {
            return response()->json([
                'message' => 'Selected user is not eligible for this project group chat.',
            ], 422);
        }

        $exists = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $targetUserId)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'User is already a participant in this conversation.',
            ], 422);
        }

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $targetUserId,
            'is_admin' => false,
        ]);

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, $user->id),
        ]);
    }

    public function removeParticipant(Request $request, Conversation $conversation, User $participantUser): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageGroupParticipants($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to manage this group chat.'], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Participants can be managed only on project group chats.'], 422);
        }

        $participantRecord = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $participantUser->id)
            ->first();

        if (! $participantRecord) {
            return response()->json([
                'message' => 'Participant not found in this conversation.',
            ], 404);
        }

        if ((bool) $participantRecord->is_admin && $this->groupAdminCount($conversation->id) <= 1) {
            return response()->json([
                'message' => 'Cannot remove the last group admin.',
            ], 422);
        }

        $participantRecord->delete();

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, $user->id),
        ]);
    }

    public function promoteParticipantToAdmin(Request $request, Conversation $conversation, User $participantUser): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageGroupParticipants($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to manage this group chat.'], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Only group chats support admin permissions.'], 422);
        }

        $participantRecord = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $participantUser->id)
            ->first();

        if (! $participantRecord) {
            return response()->json([
                'message' => 'Participant not found in this conversation.',
            ], 404);
        }

        if (! (bool) $participantRecord->is_admin) {
            $participantRecord->update(['is_admin' => true]);
        }

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, (int) $user->id),
        ]);
    }

    public function demoteParticipantFromAdmin(Request $request, Conversation $conversation, User $participantUser): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageGroupParticipants($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to manage this group chat.'], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Only group chats support admin permissions.'], 422);
        }

        $participantRecord = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $participantUser->id)
            ->first();

        if (! $participantRecord) {
            return response()->json([
                'message' => 'Participant not found in this conversation.',
            ], 404);
        }

        if ((bool) $participantRecord->is_admin && $this->groupAdminCount($conversation->id) <= 1) {
            return response()->json([
                'message' => 'Cannot demote the last group admin.',
            ], 422);
        }

        if ((bool) $participantRecord->is_admin) {
            $participantRecord->update(['is_admin' => false]);
        }

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($conversation, (int) $user->id),
        ]);
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

        if (! $this->canUseConversation($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $conversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
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

        if (! $this->canUseConversation($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $perPage = (int) $request->integer('per_page', 50);
        $perPage = max(1, min(100, $perPage));

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with([
                'senderUser:id,name,email,role',
                'senderUser.studentProfile:id,user_id,avatar_path',
                'senderUser.companyProfile:id,user_id,logo_path',
            ])
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
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
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

        if (! $this->canUseConversation($user, $conversation)) {
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

        if (! $this->canUseConversation($user, $conversation)) {
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

        $message->load([
            'senderUser:id,name,email,role',
            'senderUser.studentProfile:id,user_id,avatar_path',
            'senderUser.companyProfile:id,user_id,logo_path',
        ]);
        $conversation->load([
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
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

        if (! $this->canUseConversation($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to access this conversation.'], 403);
        }

        $validated = $request->validate([
            'is_typing' => ['required', 'boolean'],
        ]);

        broadcast(new UserTyping($conversation, $user, (bool) $validated['is_typing']))->toOthers();

        return response()->json(['data' => ['ok' => true]]);
    }

    public function updateConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageGroupParticipants($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to manage this group chat.'], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Only group chats can be updated.'], 422);
        }

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'min:3', 'max:160'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $updates = [];

        if (array_key_exists('subject', $validated)) {
            $subject = trim((string) ($validated['subject'] ?? ''));
            if ($subject !== '') {
                $updates['subject'] = $subject;
            }
        }

        $avatarPath = is_string($conversation->avatar_path) ? $conversation->avatar_path : null;

        if ((bool) ($validated['remove_avatar'] ?? false) && is_string($avatarPath) && $avatarPath !== '') {
            Storage::disk(self::GROUP_AVATAR_DISK)->delete($avatarPath);
            $avatarPath = null;
        }

        if (array_key_exists('avatar', $validated) && $validated['avatar']) {
            $previousAvatarPath = $avatarPath;

            try {
                $storedAvatarPath = $validated['avatar']->store('group-avatars/' . $conversation->id, self::GROUP_AVATAR_DISK);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Failed to store group avatar.',
                    'detail' => (bool) config('app.debug', false) ? $e->getMessage() : null,
                ], 500);
            }

            if (! is_string($storedAvatarPath) || $storedAvatarPath === '') {
                report(new \RuntimeException('Storage write returned false for group avatar upload on disk ' . self::GROUP_AVATAR_DISK . '.'));

                return response()->json([
                    'message' => 'Failed to store group avatar.',
                    'detail' => (bool) config('app.debug', false)
                        ? 'Storage write returned false. Verify MinIO endpoint, credentials, bucket, and AWS_USE_PATH_STYLE_ENDPOINT.'
                        : null,
                ], 500);
            }

            $avatarPath = $storedAvatarPath;

            if (is_string($previousAvatarPath) && $previousAvatarPath !== '') {
                Storage::disk(self::GROUP_AVATAR_DISK)->delete($previousAvatarPath);
            }
        }

        if ($avatarPath !== $conversation->avatar_path) {
            $updates['avatar_path'] = $avatarPath;
        }

        if ($updates !== []) {
            $conversation->update($updates);
        }

        $updatedConversation = Conversation::query()->findOrFail($conversation->id);
        $updatedConversation->load([
            'project:id,title',
            'participantRecords:id,conversation_id,user_id,is_admin,last_read_message_id,last_read_at',
            'participantRecords.user:id,name,email,role',
            'messages:id,conversation_id,sender_user_id,body,created_at',
            'messages.senderUser:id,name,email,role',
        ]);

        return response()->json([
            'data' => $this->transformConversation($updatedConversation, (int) $user->id),
        ]);
    }

    public function destroyConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->canManageGroupParticipants($user, $conversation)) {
            return response()->json(['message' => 'You are not allowed to manage this group chat.'], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json(['message' => 'Only group chats can be deleted.'], 422);
        }

        $avatarPath = is_string($conversation->avatar_path) ? $conversation->avatar_path : '';
        if ($avatarPath !== '') {
            Storage::disk(self::GROUP_AVATAR_DISK)->delete($avatarPath);
        }

        $conversationId = (int) $conversation->id;
        $conversation->delete();

        return response()->json([
            'data' => [
                'ok' => true,
                'conversation_id' => $conversationId,
            ],
        ]);
    }

    public function signedConversationAvatar(Request $request, Conversation $conversation)
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired group avatar URL.',
            ], 403);
        }

        if ($conversation->type !== 'group') {
            return response()->json([
                'message' => 'Group avatar not found.',
            ], 404);
        }

        $avatarPath = Conversation::query()
            ->where('id', $conversation->id)
            ->value('avatar_path');

        if (! is_string($avatarPath) || $avatarPath === '') {
            return response()->json([
                'message' => 'Group avatar not found.',
            ], 404);
        }

        if (! Storage::disk(self::GROUP_AVATAR_DISK)->exists($avatarPath)) {
            return response()->json([
                'message' => 'Group avatar not found in storage.',
            ], 404);
        }

        $stream = Storage::disk(self::GROUP_AVATAR_DISK)->readStream($avatarPath);

        if (! is_resource($stream)) {
            return response()->json([
                'message' => 'Group avatar could not be streamed from storage.',
            ], 500);
        }

        $disk = Storage::disk(self::GROUP_AVATAR_DISK);
        $mimeType = $disk instanceof FilesystemAdapter
            ? ($disk->mimeType($avatarPath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function isParticipant(Conversation $conversation, int $userId): bool
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function canUseConversation(User $user, Conversation $conversation): bool
    {
        if (! $this->isParticipant($conversation, (int) $user->id)) {
            return false;
        }

        if ($conversation->type !== 'direct') {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $participantIds = ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->values();

        $otherUserId = $participantIds
            ->first(fn(int $participantId) => $participantId !== (int) $user->id);

        if (! is_int($otherUserId)) {
            return false;
        }

        $allowedRecipientIds = $this->allowedDirectRecipientIds($user);

        return in_array($otherUserId, $allowedRecipientIds, true);
    }

    private function canDirectMessage(User $sender, User $recipient): bool
    {
        if ($sender->id === $recipient->id) {
            return false;
        }

        if ($sender->role === 'admin') {
            return true;
        }

        $allowedRecipientIds = $this->allowedDirectRecipientIds($sender);

        return in_array((int) $recipient->id, $allowedRecipientIds, true);
    }

    private function canManageGroupParticipants(User $user, Conversation $conversation): bool
    {
        if ($conversation->type !== 'group') {
            return false;
        }

        return $this->isGroupAdmin($conversation->id, (int) $user->id);
    }

    private function isGroupAdmin(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('is_admin', true)
            ->exists();
    }

    private function groupAdminCount(int $conversationId): int
    {
        return ConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('is_admin', true)
            ->count();
    }

    /**
     * @return array<int>
     */
    private function allowedDirectRecipientIds(User $user): array
    {
        if ($user->role === 'admin') {
            return User::query()
                ->where('id', '!=', $user->id)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $adminIds = User::query()
            ->where('role', 'admin')
            ->where('id', '!=', $user->id)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        if ($user->role === 'student') {
            $companyIds = Application::query()
                ->where('student_user_id', $user->id)
                ->where('status', 'accepted')
                ->with('project:id,company_user_id')
                ->get()
                ->pluck('project.company_user_id')
                ->filter(fn($companyUserId) => is_numeric($companyUserId))
                ->map(fn($companyUserId) => (int) $companyUserId)
                ->values()
                ->all();

            return collect(array_merge($companyIds, $adminIds))
                ->unique()
                ->values()
                ->all();
        }

        if ($user->role === 'company') {
            $studentIds = Application::query()
                ->where('status', 'accepted')
                ->whereHas('project', fn($query) => $query->where('company_user_id', $user->id))
                ->pluck('student_user_id')
                ->map(fn($studentUserId) => (int) $studentUserId)
                ->unique()
                ->values()
                ->all();

            return collect(array_merge($studentIds, $adminIds))
                ->unique()
                ->values()
                ->all();
        }

        return $adminIds;
    }

    /**
     * @return array<int>
     */
    private function allowedGroupParticipantIdsForProject(User $user, int $projectId): array
    {
        $project = Project::query()->find($projectId);
        if (! $project) {
            return [];
        }

        $acceptedStudentIds = Application::query()
            ->where('project_id', $projectId)
            ->where('status', 'accepted')
            ->pluck('student_user_id')
            ->map(fn($studentUserId) => (int) $studentUserId)
            ->unique()
            ->values()
            ->all();

        if ($user->role === 'admin') {
            return collect(array_merge($acceptedStudentIds, [(int) $project->company_user_id]))
                ->filter(fn(int $id) => $id > 0 && $id !== (int) $user->id)
                ->unique()
                ->values()
                ->all();
        }

        if ($user->role === 'company') {
            if ((int) $project->company_user_id !== (int) $user->id) {
                return [];
            }

            return collect(array_merge($acceptedStudentIds, [(int) $project->company_user_id]))
                ->filter(fn(int $id) => $id > 0 && $id !== (int) $user->id)
                ->unique()
                ->values()
                ->all();
        }

        if ($user->role === 'student') {
            $studentAccepted = Application::query()
                ->where('project_id', $projectId)
                ->where('student_user_id', $user->id)
                ->where('status', 'accepted')
                ->exists();

            if (! $studentAccepted) {
                return [];
            }

            return collect(array_merge($acceptedStudentIds, [(int) $project->company_user_id]))
                ->filter(fn(int $id) => $id > 0 && $id !== (int) $user->id)
                ->unique()
                ->values()
                ->all();
        }

        return [];
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
            'type' => $conversation->type,
            'subject' => $conversation->subject,
            'avatar_url' => $this->conversationAvatarUrl($conversation),
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
                    'avatar_url' => $participant->user?->avatar_url,
                    'is_admin' => (bool) ($participant->is_admin ?? false),
                ])->values()
                : [],
            'last_message' => $lastMessage ? $this->transformMessage($lastMessage, $conversation) : null,
            'unread_count' => $unreadCount,
            'created_at' => optional($conversation->created_at)->toISOString(),
        ];
    }

    private function conversationAvatarUrl(Conversation $conversation): ?string
    {
        $avatarPath = is_string($conversation->avatar_path) ? trim($conversation->avatar_path) : '';
        if ($avatarPath === '') {
            return null;
        }

        $ttlMinutes = max(1, (int) config('filesystems.avatar_temporary_url_minutes', 60));

        return URL::temporarySignedRoute(
            'conversations.avatar.signed',
            now()->addMinutes($ttlMinutes),
            ['conversation' => $conversation->id]
        );
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
                'avatar_url' => $message->senderUser?->avatar_url,
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
