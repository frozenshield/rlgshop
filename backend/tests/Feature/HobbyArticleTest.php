<?php

namespace Tests\Feature;

use App\Models\HobbyArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HobbyArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_hobby_articles(): void
    {
        HobbyArticle::create([
            'slug' => 'test-article-1',
            'title' => 'Test Article 1',
            'category' => 'TCG Strategy',
            'author' => 'Author One',
            'status' => 'Published',
            'summary' => 'Summary 1',
            'content' => 'Content 1',
        ]);

        $response = $this->getJson('/api/hobby-articles');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'count',
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'title',
                        'category',
                        'author',
                        'status',
                        'summary',
                        'content',
                    ],
                ],
            ]);
    }

    public function test_can_filter_hobby_articles_by_category_and_status(): void
    {
        HobbyArticle::create([
            'slug' => 'published-gunpla',
            'title' => 'Gunpla Tips',
            'category' => 'Gunpla & Modeling',
            'status' => 'Published',
        ]);

        HobbyArticle::create([
            'slug' => 'draft-tcg',
            'title' => 'TCG Secret Deck',
            'category' => 'TCG Strategy',
            'status' => 'Draft',
        ]);

        $res1 = $this->getJson('/api/hobby-articles?category=Gunpla+%26+Modeling');
        $res1->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'published-gunpla');

        $res2 = $this->getJson('/api/hobby-articles?status=Draft');
        $res2->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'draft-tcg');
    }

    public function test_can_create_hobby_article_with_auto_slug_and_sources(): void
    {
        $payload = [
            'title' => 'Mastering Tournament Shuffling and Sleeving',
            'category' => 'TCG Strategy',
            'author' => 'Judge Master',
            'summary' => 'Proper double sleeving avoids DQ and penalties.',
            'content' => 'Full detailed guide on riffle vs mash shuffling.',
            'sources' => [
                [
                    'name' => 'Official Bandai Floor Rules',
                    'url' => 'https://en.onepiece-cardgame.com/rules/',
                    'note' => 'Penalty guidelines',
                ],
            ],
            'is_featured' => true,
        ];

        $response = $this->postJson('/api/hobby-articles', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Mastering Tournament Shuffling and Sleeving')
            ->assertJsonPath('data.slug', 'mastering-tournament-shuffling-and-sleeving')
            ->assertJsonPath('data.is_featured', true);

        $this->assertDatabaseHas('hobby_articles', [
            'slug' => 'mastering-tournament-shuffling-and-sleeving',
            'is_featured' => 1,
        ]);
    }

    public function test_can_retrieve_article_by_slug_and_increments_views(): void
    {
        $article = HobbyArticle::create([
            'slug' => 'view-counter-article',
            'title' => 'View Counter Article',
            'category' => 'Care & Preservation',
            'views_count' => 10,
        ]);

        $response = $this->getJson('/api/hobby-articles/view-counter-article');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.views_count', 11);

        $this->assertEquals(11, $article->fresh()->views_count);
    }

    public function test_can_update_hobby_article(): void
    {
        $article = HobbyArticle::create([
            'slug' => 'update-me',
            'title' => 'Initial Title',
            'category' => 'TCG Strategy',
        ]);

        $response = $this->putJson("/api/hobby-articles/{$article->id}", [
            'title' => 'Updated Title',
            'status' => 'Archived',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'Archived');

        $this->assertDatabaseHas('hobby_articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
            'status' => 'Archived',
        ]);
    }

    public function test_can_delete_hobby_article(): void
    {
        $article = HobbyArticle::create([
            'slug' => 'delete-me',
            'title' => 'Delete Me',
            'category' => 'TCG Strategy',
        ]);

        $response = $this->deleteJson("/api/hobby-articles/{$article->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('hobby_articles', [
            'id' => $article->id,
        ]);
    }

    public function test_cms_endpoint_returns_hobby_articles_in_blogs_key(): void
    {
        HobbyArticle::create([
            'slug' => 'synced-article',
            'title' => 'Synced Article for Storefront',
            'category' => 'TCG Strategy',
            'author' => 'Sync Author',
            'status' => 'Published',
            'summary' => 'Sync summary',
            'content' => 'Sync content',
        ]);

        $response = $this->getJson('/api/cms');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['banners', 'pages', 'blogs']]);

        $blogs = $response->json('data.blogs');
        $this->assertNotEmpty($blogs);
        $this->assertEquals('Synced Article for Storefront', $blogs[0]['title']);
    }
}
