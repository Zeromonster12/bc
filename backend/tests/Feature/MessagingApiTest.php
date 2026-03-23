<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_list_and_open_direct_conversation(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        Sanctum::actingAs($student);

        $create = $this->postJson('/api/conversations', [
            'recipient_user_id' => $company->id,
        ]);

        $create->assertCreated();
        $conversationId = (int) $create->json('data.id');

        $list = $this->getJson('/api/conversations');
        $list->assertOk();
        $list->assertJsonPath('data.0.id', $conversationId);
        $list->assertJsonPath('data.0.subject', 'Direct chat');

        $show = $this->getJson('/api/conversations/' . $conversationId);
        $show->assertOk();
        $show->assertJsonPath('data.id', $conversationId);
        $show->assertJsonPath('data.participants.0.email', $student->email);
    }

    public function test_participant_can_send_message_and_fetch_history(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $conversation = Conversation::query()->create([
            'type' => 'direct',
            'subject' => 'Build status',
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
        ]);
        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $company->id,
        ]);

        Sanctum::actingAs($student);

        $send = $this->postJson('/api/conversations/' . $conversation->id . '/messages', [
            'body' => 'I pushed the latest change set for review.',
        ]);

        $send->assertCreated();
        $send->assertJsonPath('data.sender.id', $student->id);
        $send->assertJsonPath('data.body', 'I pushed the latest change set for review.');

        $history = $this->getJson('/api/conversations/' . $conversation->id . '/messages');
        $history->assertOk();
        $history->assertJsonPath('data.0.body', 'I pushed the latest change set for review.');
    }

    public function test_non_participant_cannot_access_conversation(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);
        $other = User::factory()->create(['role' => 'student']);

        $conversation = Conversation::query()->create([
            'type' => 'direct',
            'subject' => 'Private thread',
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
        ]);
        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $company->id,
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_user_id' => $student->id,
            'body' => 'This should stay private.',
        ]);

        Sanctum::actingAs($other);

        $show = $this->getJson('/api/conversations/' . $conversation->id);
        $show->assertForbidden();

        $messages = $this->getJson('/api/conversations/' . $conversation->id . '/messages');
        $messages->assertForbidden();
    }

    public function test_participant_can_set_and_clear_typing_status(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $company = User::factory()->create(['role' => 'company']);

        $conversation = Conversation::query()->create([
            'type' => 'direct',
            'subject' => 'Typing test',
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
        ]);

        ConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $company->id,
        ]);

        Sanctum::actingAs($student);

        $setTyping = $this->postJson('/api/conversations/' . $conversation->id . '/typing', [
            'is_typing' => true,
        ]);

        $setTyping->assertOk();

        Sanctum::actingAs($student);

        $stopTyping = $this->postJson('/api/conversations/' . $conversation->id . '/typing', [
            'is_typing' => false,
        ]);

        $stopTyping->assertOk();
    }

    public function test_user_can_search_chat_recipients_by_name_or_email(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Alice Sender',
            'email' => 'alice@example.com',
        ]);
        $target = User::factory()->create([
            'role' => 'company',
            'name' => 'Bob Recruiter',
            'email' => 'bob@example.com',
        ]);

        Sanctum::actingAs($student);

        $searchByName = $this->getJson('/api/conversation-users?q=Bob');
        $searchByName->assertOk();
        $searchByName->assertJsonPath('data.0.id', $target->id);

        $searchByEmail = $this->getJson('/api/conversation-users?q=@example.com');
        $searchByEmail->assertOk();
        $searchByEmail->assertJsonMissing([
            'id' => $student->id,
        ]);
    }
}
