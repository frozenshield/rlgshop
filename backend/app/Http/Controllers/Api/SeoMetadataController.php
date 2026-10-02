<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SeoMetadata;
use App\Services\SeoMetadataGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoMetadataController extends Controller
{
    /**
     * Display a listing of SEO metadata configurations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeoMetadata::query();

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        $records = $query->latest()->get();

        // If table is empty, seed defaults for core storefront routes
        if ($records->isEmpty()) {
            $this->seedDefaultSeoRoutes();
            $records = SeoMetadata::latest()->get();
        }

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    /**
     * Get SEO metadata for a specific storefront route or entity.
     */
    public function getByRoute(Request $request): JsonResponse
    {
        $path = $request->input('route', '/');
        $cleanPath = '/'.ltrim($path, '/');

        $seo = SeoMetadata::where('route_path', $cleanPath)
            ->where('is_active', true)
            ->first();

        if (! $seo) {
            // Check global fallback
            $seo = SeoMetadata::where('entity_type', 'global')
                ->where('is_active', true)
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $seo,
        ]);
    }

    /**
     * Generate SEO metadata via AI (Gemini).
     */
    public function generateAi(Request $request, SeoMetadataGeneratorService $generator): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'nullable|string|in:global,product,category,article,custom_page',
            'entity_id' => 'nullable|integer',
            'page_name' => 'nullable|string|max:255',
            'route_path' => 'nullable|string|max:255',
            'target_keyword' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string',
            'price' => 'nullable',
            'image_url' => 'nullable|string',
        ]);

        $generated = $generator->generate($validated);

        return response()->json([
            'success' => true,
            'message' => 'SEO metadata generated successfully with AI',
            'data' => $generated,
        ]);
    }

    /**
     * Store a newly created SEO metadata record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'nullable|integer',
            'page_name' => 'required|string|max:255',
            'route_path' => 'required|string|max:255',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'meta_keywords' => 'nullable|string',
            'focus_keyword' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image_url' => 'nullable|string|max:500',
            'twitter_card' => 'nullable|string|max:50',
            'robots' => 'nullable|string|max:100',
            'structured_data_json' => 'nullable|array',
            'seo_score' => 'nullable|integer|min:0|max:100',
            'ai_generated' => 'nullable|boolean',
            'ai_model' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $seo = SeoMetadata::updateOrCreate(
            [
                'route_path' => $validated['route_path'],
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'SEO metadata saved successfully',
            'data' => $seo,
        ], 201);
    }

    /**
     * Display the specified SEO record.
     */
    public function show(int $id): JsonResponse
    {
        $seo = SeoMetadata::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $seo,
        ]);
    }

    /**
     * Update the specified SEO metadata.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $seo = SeoMetadata::findOrFail($id);

        $validated = $request->validate([
            'entity_type' => 'sometimes|string|max:50',
            'entity_id' => 'nullable|integer',
            'page_name' => 'sometimes|string|max:255',
            'route_path' => 'sometimes|string|max:255',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string',
            'meta_keywords' => 'nullable|string',
            'focus_keyword' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image_url' => 'nullable|string|max:500',
            'twitter_card' => 'nullable|string|max:50',
            'robots' => 'nullable|string|max:100',
            'structured_data_json' => 'nullable|array',
            'seo_score' => 'nullable|integer|min:0|max:100',
            'ai_generated' => 'nullable|boolean',
            'ai_model' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $seo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'SEO metadata updated successfully',
            'data' => $seo,
        ]);
    }

    /**
     * Delete SEO metadata record.
     */
    public function destroy(int $id): JsonResponse
    {
        $seo = SeoMetadata::findOrFail($id);
        $seo->delete();

        return response()->json([
            'success' => true,
            'message' => 'SEO metadata deleted successfully',
        ]);
    }

    /**
     * Seed initial sensible SEO routes.
     */
    protected function seedDefaultSeoRoutes(): void
    {
        SeoMetadata::create([
            'entity_type' => 'global',
            'page_name' => 'Storefront Homepage (Global)',
            'route_path' => '/',
            'meta_title' => 'RLG Hobby Shop | TCG, Model Kits & Collectibles Philippines',
            'meta_description' => 'Shop authentic factory-sealed Pokémon & One Piece TCG booster boxes, Japanese Bandai Gunpla model kits, and scale anime figures with secure nationwide shipping.',
            'meta_keywords' => 'Pokemon TCG Philippines, One Piece Card Game, Gunpla Manila, Bandai Model Kits, Anime Figures, TCG booster boxes',
            'focus_keyword' => 'Hobby Shop Philippines',
            'canonical_url' => 'https://rlghobby.ph/',
            'og_title' => 'RLG Hobby Shop | Authentic TCG, Gunpla & Scale Collectibles',
            'og_description' => 'Official hub for authentic Pokémon TCG, One Piece TCG, Bandai Gunpla kits, and Japanese hobby collectibles in the Philippines.',
            'og_image_url' => 'https://rlghobby.ph/logo.png',
            'twitter_card' => 'summary_large_image',
            'robots' => 'index, follow',
            'structured_data_json' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'RLG Hobby Shop',
                'url' => 'https://rlghobby.ph',
            ],
            'seo_score' => 96,
            'ai_generated' => true,
            'ai_model' => 'gemini-2.5-flash',
            'is_active' => true,
        ]);

        SeoMetadata::create([
            'entity_type' => 'category',
            'page_name' => 'Trading Card Games (TCG) Catalog',
            'route_path' => '/catalog?category=tcg',
            'meta_title' => 'Buy One Piece & Pokémon TCG Booster Boxes | RLG Hobby Shop',
            'meta_description' => 'Browse sealed One Piece (OP-01 to OP-17) & Pokémon TCG booster boxes, starter decks, and collector singles. Verified authentic with fast nationwide delivery.',
            'meta_keywords' => 'Buy One Piece TCG, Pokemon Booster Box Philippines, One Piece OP-05 Awakening, Pokemon 151 ETB',
            'focus_keyword' => 'One Piece TCG Philippines',
            'canonical_url' => 'https://rlghobby.ph/catalog?category=tcg',
            'og_title' => 'Sealed TCG Booster Boxes & Sleeves | RLG Hobby Shop',
            'og_description' => 'Top tier sealed TCG booster boxes and tournament accessories delivered safely across the Philippines.',
            'og_image_url' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=800',
            'twitter_card' => 'summary_large_image',
            'robots' => 'index, follow',
            'seo_score' => 92,
            'ai_generated' => true,
            'ai_model' => 'gemini-2.5-flash',
            'is_active' => true,
        ]);

        // Add first product if available
        $product = Product::first();
        if ($product) {
            SeoMetadata::create([
                'entity_type' => 'product',
                'entity_id' => $product->id,
                'page_name' => "Product: {$product->name}",
                'route_path' => "/products/{$product->id}",
                'meta_title' => mb_substr("{$product->name} | RLG Hobby Shop PH", 0, 60),
                'meta_description' => mb_substr("Order {$product->name} online. 100% authentic collector stock with secure insured dispatch across the Philippines. Buy now!", 0, 155),
                'meta_keywords' => "{$product->name}, buy {$product->name}, sealed collectible, RLG shop",
                'focus_keyword' => $product->name,
                'canonical_url' => "https://rlghobby.ph/products/{$product->id}",
                'og_title' => $product->name,
                'og_description' => mb_substr($product->description ?? "Authentic collector item at RLG Hobby Shop", 0, 150),
                'og_image_url' => $product->image_url,
                'twitter_card' => 'summary_large_image',
                'robots' => 'index, follow',
                'structured_data_json' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => $product->name,
                    'price' => (string) $product->price,
                    'priceCurrency' => 'PHP',
                ],
                'seo_score' => 95,
                'ai_generated' => true,
                'ai_model' => 'gemini-2.5-flash',
                'is_active' => true,
            ]);
        }
    }
}

