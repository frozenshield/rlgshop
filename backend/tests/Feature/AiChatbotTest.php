<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test retrieving quick prompt suggestions for the chat widget.
     */
    public function test_quick_prompts_endpoint(): void
    {
        $response = $this->getJson('/api/ai/chat/quick-prompts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['label', 'prompt'],
                ],
            ]);
    }

    /**
     * Test asking the AI chatbot about stock and product details.
     */
    public function test_chat_stock_inquiry(): void
    {
        $response = $this->postJson('/api/ai/chat', [
            'message' => 'Do you have Pokémon Scarlet & Violet 151 Elite Trainer Box in stock and how much is it?',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'reply',
                    'suggested_products',
                    'suggested_actions',
                    'model_used',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.reply'));
    }

    /**
     * Test asking the AI chatbot about shipping rates and free shipping.
     */
    public function test_chat_shipping_inquiry(): void
    {
        $response = $this->postJson('/api/ai/chat', [
            'message' => 'How much is shipping and do you offer free delivery?',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $reply = strtolower($response->json('data.reply'));
        $this->assertTrue(str_contains($reply, '150') || str_contains($reply, 'free') || str_contains($reply, '2,500') || str_contains($reply, '2500'));
    }

    /**
     * Test asking the AI chatbot about active promo codes.
     */
    public function test_chat_promo_inquiry(): void
    {
        $response = $this->postJson('/api/ai/chat', [
            'message' => 'What discount promo codes can I use today?',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $reply = $response->json('data.reply');
        $this->assertTrue(str_contains($reply, 'HOBBY10') || str_contains($reply, 'FREESHIPPH') || str_contains($reply, 'GUNPLA20') || str_contains($reply, 'coupon'));
    }

    /**
     * Test validation rejects empty message.
     */
    public function test_chat_validation_rejects_empty_message(): void
    {
        $response = $this->postJson('/api/ai/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422);
    }
}
