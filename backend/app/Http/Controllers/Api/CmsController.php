<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsContent;
use App\Models\HobbyArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends Controller
{
    protected function defaultBanners(): array
    {
        return [
            [
                'id' => 'bnr-1',
                'title' => 'Build, Collect & Battle. Your Premier Hobby Store.',
                'subtitle' => 'Discover factory-sealed Trading Card Game booster boxes, authentic Japanese Bandai Gunpla kits, detailed anime scale figures, and premium card sleeves — shipped securely across the Philippines.',
                'badgeText' => 'RLG HOBBY SHOP • OFFICIAL IMPORTS VAULT',
                'ctaText' => 'Explore All Products',
                'ctaLink' => '/catalog',
                'imageUrl' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=700&auto=format&fit=crop&q=80',
                'isActive' => true,
            ],
            [
                'id' => 'bnr-2',
                'title' => 'Level Up Your Collection: 10% - 20% Off Drops!',
                'subtitle' => 'Apply collector code HOBBY10 or GUNPLA20 at checkout on all orders!',
                'badgeText' => 'Collector Welcome Coupon ⚡',
                'ctaText' => 'Shop Deals Now',
                'ctaLink' => '/catalog',
                'imageUrl' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=700&auto=format&fit=crop&q=80',
                'isActive' => true,
            ],
        ];
    }

    protected function defaultPages(): array
    {
        return [
            'about' => "Welcome to RLG Hobby Shop, the Philippines' leading destination for authentic Japanese hobby imports, factory-sealed Trading Card Game booster boxes, Bandai Gunpla model kits, and collector display figures.\n\nFounded in 2024 by passionate hobbyists, we guarantee 100% genuine products with double-boxed collector-grade shipping armor nationwide.",
            'terms' => "All sales of factory-sealed booster boxes and graded cards are authentic. Once security seals or plastic wraps are opened or tampered with, returns cannot be accepted due to secondary market volatility.\n\nDeliveries are handled by accredited Philippine air and land logistics couriers with insured tracking.",
            'privacy' => 'RLG Hobby Shop respects your data privacy. We only collect shipping addresses, email addresses, and phone numbers necessary to deliver your hobby parcels safely. We never sell your personal information to third parties.',
            'faq' => "Q: Are all TCG booster boxes factory sealed?\nA: Yes! All Pokémon, One Piece, Hololive, and Weiß Schwarz booster boxes carry intact manufacturer shrink-wrap and security tape.\n\nQ: How fast is nationwide shipping?\nA: Metro Manila orders arrive in 1-2 business days; Provincial Luzon, Visayas, and Mindanao arrive in 3-5 business days.",
        ];
    }

    protected function defaultBlogs(): array
    {
        return [
            [
                'id' => 'post-1',
                'title' => 'Top 5 One Piece Card Game Meta Decks in OP-07 Egghead',
                'category' => 'TCG Strategy',
                'author' => 'Chief Deck Architect',
                'date' => '2026-09-20',
                'status' => 'Published',
                'summary' => 'A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists.',
                'content' => "A deep dive into Yellow Vegapunk, Blue Doflamingo, and Green Bonney tournament tier lists. Learn matchup mulligans, leader powers, and counter strategies to pilot your deck into top cut.\n\nKey staples like 8c Katakuri and 10c Ace remain dominant across regional qualifiers.",
            ],
            [
                'id' => 'post-2',
                'title' => 'Beginner Guide: Essential Tools for Building Your First RG Gunpla',
                'category' => 'Gunpla & Modeling',
                'author' => 'Master Builder Ken',
                'date' => '2026-09-15',
                'status' => 'Published',
                'summary' => 'Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained.',
                'content' => "Single blade nippers, sanding sponges, panel lining markers, and topcoat finishes explained. Protect your parts and achieve pristine nub removal on Real Grade kits without plastic stress marks.\n\nFinish with a matte topcoat for a display-worthy anime-accurate finish.",
            ],
        ];
    }

    /**
     * Get or initialize CMS content for a given key.
     */
    protected function getContent(string $key, array $defaults)
    {
        $record = CmsContent::firstOrCreate(
            ['key' => $key],
            ['value' => $defaults]
        );

        return $record->value ?? $defaults;
    }

    /**
     * Helper to retrieve blogs from hobby_articles table or fallback.
     */
    protected function getBlogsList(): array
    {
        $articles = HobbyArticle::orderBy('published_at', 'desc')->orderBy('id', 'desc')->get();

        if ($articles->isNotEmpty()) {
            return $articles->map(function (HobbyArticle $article): array {
                return [
                    'id' => (string) $article->id,
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'category' => $article->category,
                    'author' => $article->author,
                    'date' => $article->published_at ? $article->published_at->format('Y-m-d') : now()->toDateString(),
                    'status' => $article->status,
                    'summary' => $article->summary,
                    'content' => $article->content,
                    'image_url' => $article->image_url,
                    'sources' => $article->sources,
                    'is_featured' => $article->is_featured,
                    'views_count' => $article->views_count,
                ];
            })->all();
        }

        return $this->getContent('blogs', $this->defaultBlogs());
    }

    /**
     * Retrieve all CMS contents in one payload.
     */
    public function index(): JsonResponse
    {
        $banners = $this->getContent('banners', $this->defaultBanners());
        $pages = $this->getContent('pages', $this->defaultPages());
        $blogs = $this->getBlogsList();

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'pages' => $pages,
                'blogs' => $blogs,
            ],
        ]);
    }

    /**
     * Retrieve specific CMS key.
     */
    public function show(string $key): JsonResponse
    {
        if ($key === 'blogs') {
            return response()->json([
                'success' => true,
                'key' => 'blogs',
                'data' => $this->getBlogsList(),
            ]);
        }

        $defaults = match ($key) {
            'banners' => $this->defaultBanners(),
            'pages' => $this->defaultPages(),
            default => [],
        };

        $content = $this->getContent($key, $defaults);

        return response()->json([
            'success' => true,
            'key' => $key,
            'data' => $content,
        ]);
    }

    /**
     * Update CMS content for a specific key (e.g. banners, pages, blogs).
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $value = $request->input('value') ?? $request->input('data') ?? $request->all();

        // If wrapped in 'value' or direct array
        if ($request->has('value')) {
            $value = $request->input('value');
        }

        $record = CmsContent::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // When blogs are updated via CMS, synchronize them into hobby_articles table
        if ($key === 'blogs' && is_array($value)) {
            foreach ($value as $item) {
                if (! is_array($item) || empty($item['title'])) {
                    continue;
                }

                $title = trim($item['title']);
                $slug = ! empty($item['slug']) ? $item['slug'] : Str::slug($title);
                $articleId = isset($item['id']) && is_numeric($item['id']) ? (int) $item['id'] : null;

                $updateData = [
                    'title' => $title,
                    'category' => $item['category'] ?? 'TCG Strategy',
                    'author' => $item['author'] ?? 'RLG Editorial Staff',
                    'published_at' => $item['date'] ?? $item['published_at'] ?? now()->toDateString(),
                    'status' => $item['status'] ?? 'Published',
                    'summary' => $item['summary'] ?? '',
                    'content' => $item['content'] ?? $item['summary'] ?? '',
                    'image_url' => $item['image_url'] ?? null,
                    'sources' => $item['sources'] ?? null,
                    'is_featured' => ! empty($item['is_featured']),
                ];

                if ($articleId && HobbyArticle::find($articleId)) {
                    HobbyArticle::where('id', $articleId)->update($updateData);
                } else {
                    HobbyArticle::updateOrCreate(
                        ['slug' => $slug],
                        array_merge($updateData, ['slug' => $slug])
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($key).' updated successfully and published to storefront.',
            'data' => $record->value,
        ]);
    }
}
