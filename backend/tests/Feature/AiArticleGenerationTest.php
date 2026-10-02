<?php

namespace Tests\Feature;

use Tests\TestCase;

class AiArticleGenerationTest extends TestCase
{
    /**
     * Test generating an article with valid parameters.
     */
    public function test_can_generate_hobby_article_with_sources(): void
    {
        $payload = [
            'topic' => 'One Piece OP-08 Two Legends Meta Decks and Matchups',
            'category' => 'TCG Strategy',
            'audience' => 'Competitive',
            'include_sources' => true,
        ];

        $response = $this->postJson('/api/ai/generate-article', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'title',
                    'category',
                    'author',
                    'summary',
                    'content',
                    'sources',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.title'));
        $this->assertNotEmpty($response->json('data.content'));
        $this->assertIsArray($response->json('data.sources'));
    }

    /**
     * Test validation error when topic is missing.
     */
    public function test_fails_when_topic_is_missing(): void
    {
        $response = $this->postJson('/api/ai/generate-article', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['topic']);
    }
}
