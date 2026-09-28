<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\ChatConversationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatConversationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerA;

    protected User $customerB;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerA = User::firstOrCreate(
            ['email' => 'marcus.tan@example.com'],
            ['name' => 'Marcus Tan', 'user_type' => 'customer']
        );

        $this->customerB = User::firstOrCreate(
            ['email' => 'elena.reyes@example.com'],
            ['name' => 'Elena Reyes', 'user_type' => 'customer']
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@rlghobby.com'],
            ['name' => 'Admin Chief', 'user_type' => 'admin']
        );

        $this->seed(ChatConversationSeeder::class);
    }

    public function test_unauthenticated_user_cannot_fetch_conversations(): void
    {
        $response = $this->getJson('/api/conversations');
        $response->assertStatus(200);
        $response->assertJsonPath('count', 0);
        $this->assertEmpty($response->json('data'));
    }

    public function test_customer_only_sees_their_own_conversation(): void
    {
        Sanctum::actingAs($this->customerA);
        $response = $this->getJson('/api/conversations');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $conv) {
            $this->assertEquals($this->customerA->id, $conv['customer_id']);
        }
    }

    public function test_customer_and_admin_can_have_multi_turn_chat_conversation(): void
    {
        Sanctum::actingAs($this->customerA);

        // 1. Get or create conversation room
        $startResponse = $this->postJson('/api/conversations', [
            'message' => 'Do you have serialized Charizard SAR in PSA 10?',
        ]);

        $startResponse->assertStatus(201);
        $conversationId = $startResponse->json('data.id');
        $this->assertNotNull($conversationId);

        // 2. Customer sends a second message in thread
        $msgResponse = $this->postJson("/api/conversations/{$conversationId}/messages", [
            'content' => 'Also wondering if you ship to Cebu City?',
        ]);

        $msgResponse->assertStatus(201);
        $msgResponse->assertJsonPath('data.content', 'Also wondering if you ship to Cebu City?');
        $msgResponse->assertJsonPath('data.sender_id', $this->customerA->id);

        // 3. Admin switches into the chat and replies
        Sanctum::actingAs($this->adminUser);
        $adminReplyResponse = $this->postJson("/api/conversations/{$conversationId}/messages", [
            'content' => 'Yes Marcus, we have 1 PSA 10 Gem Mint in stock and ship nationwide via LBC or J&T!',
        ]);

        $adminReplyResponse->assertStatus(201);
        $adminReplyResponse->assertJsonPath('data.sender_id', $this->adminUser->id);

        // 4. Fetch message history
        $historyResponse = $this->getJson("/api/conversations/{$conversationId}/messages");
        $historyResponse->assertStatus(200);
        $messages = $historyResponse->json('data');

        $contents = collect($messages)->pluck('content')->all();
        $this->assertContains('Do you have serialized Charizard SAR in PSA 10?', $contents);
        $this->assertContains('Also wondering if you ship to Cebu City?', $contents);
        $this->assertContains('Yes Marcus, we have 1 PSA 10 Gem Mint in stock and ship nationwide via LBC or J&T!', $contents);
    }

    public function test_messages_are_marked_as_read_when_fetched_by_recipient(): void
    {
        $conversation = Conversation::where('customer_id', $this->customerA->id)->first();
        $this->assertNotNull($conversation);

        // Create an unread message sent by admin
        $adminMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->adminUser->id,
            'content' => 'Your tracking # has been updated.',
            'is_read' => false,
        ]);

        // Customer views the messages
        Sanctum::actingAs($this->customerA);
        $this->getJson("/api/conversations/{$conversation->id}/messages");

        // Verify message is now marked as read
        $adminMessage->refresh();
        $this->assertTrue((bool) $adminMessage->is_read);
    }

    public function test_customer_cannot_access_another_customers_conversation_room(): void
    {
        $conversationA = Conversation::where('customer_id', $this->customerA->id)->first();
        $this->assertNotNull($conversationA);

        // Customer B tries to view Customer A's conversation room
        Sanctum::actingAs($this->customerB);
        $response = $this->getJson("/api/conversations/{$conversationA->id}");
        $response->assertStatus(403);

        // Customer B tries to view messages in Customer A's conversation
        $messagesResponse = $this->getJson("/api/conversations/{$conversationA->id}/messages");
        $messagesResponse->assertStatus(403);

        // Customer B tries to send message into Customer A's conversation
        $sendResponse = $this->postJson("/api/conversations/{$conversationA->id}/messages", [
            'content' => 'Unauthorized intruder message',
        ]);
        $sendResponse->assertStatus(403);
    }

    public function test_admin_can_view_all_conversations_and_update_status(): void
    {
        Sanctum::actingAs($this->adminUser);

        // List all conversations
        $response = $this->getJson('/api/conversations');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, $response->json('count'));

        // Update status of conversation to resolved
        $conversation = Conversation::first();
        $updateResponse = $this->patchJson("/api/conversations/{$conversation->id}/status", [
            'status' => 'resolved',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.status', 'resolved');

        $conversation->refresh();
        $this->assertEquals('resolved', $conversation->status);
    }

    public function test_customer_message_auto_reopens_resolved_conversation(): void
    {
        $conversation = Conversation::where('customer_id', $this->customerA->id)->first();
        $conversation->update(['status' => 'resolved']);

        Sanctum::actingAs($this->customerA);
        $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'I have a follow-up question regarding my order packaging.',
        ]);

        $conversation->refresh();
        $this->assertEquals('active', $conversation->status);
    }
}
