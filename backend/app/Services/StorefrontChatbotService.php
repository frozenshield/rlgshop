<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PromoCode;
use App\Models\RefPokemonSet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class StorefrontChatbotService
{
    /**
     * Process a customer message and return an AI-generated reply with contextual products.
     *
     * @param  string  $message  The customer prompt
     * @param  array<int, array{role: string, content: string}>  $history  Conversation history
     * @return array{
     *     reply: string,
     *     suggested_products: array<int, array<string, mixed>>,
     *     suggested_actions: array<int, string>,
     *     model_used: string
     * }
     */
    public function reply(string $message, array $history = []): array
    {
        $products = Product::with(['brand', 'category', 'condition'])->where('status', 'active')->get();
        $promos = PromoCode::where('is_active', true)->get();
        $pokemonSets = RefPokemonSet::orderBy('release_order')->get();

        // 1. First attempt generation via Google Gemini API
        $apiKey = config('services.gemini.api_key');
        if (! empty($apiKey)) {
            try {
                return $this->generateWithGemini($message, $history, $products, $promos, $pokemonSets);
            } catch (Throwable $e) {
                Log::warning('Storefront chatbot Gemini API error: '.$e->getMessage().'. Using intelligent local fallback.');
            }
        }

        // 2. Fallback to smart rule-based database engine if Gemini is unavailable
        return $this->generateLocalFallback($message, $products, $promos, $pokemonSets);
    }

    /**
     * Generate response using Google Gemini API with real-time store catalog injection.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, PromoCode>  $promos
     * @param  Collection<int, RefPokemonSet>  $pokemonSets
     * @return array{
     *     reply: string,
     *     suggested_products: array<int, array<string, mixed>>,
     *     suggested_actions: array<int, string>,
     *     model_used: string
     * }
     */
    protected function generateWithGemini(string $message, array $history, Collection $products, Collection $promos, Collection $pokemonSets): array
    {
        $apiKey = config('services.gemini.api_key');
        $primaryModel = config('services.gemini.model', 'gemini-3.8-flash');
        $models = array_unique([$primaryModel, 'gemini-3.8-flash', 'gemini-3.5-flash-lite']);

        $catalogSummary = $this->buildCatalogContext($products);
        $promoSummary = $this->buildPromoContext($promos);
        $pokemonSetSummary = $this->buildPokemonSetContext($pokemonSets);

        $systemInstruction = <<<EOT
You are "Aiko" (愛子), the cheerful, highly knowledgeable, and enthusiastic AI store assistant for "RLG Hobby Shop".
RLG Hobby Shop is the premier Philippine collector's vault for 100% authentic Japanese imports: Pokémon TCG, One Piece TCG, Hololive OCG, Bandai Gunpla Model Kits, and Anime Figures & Nendoroids.

YOUR RESPONSIBILITIES:
1. Answer customer questions about product availability, stock count, prices in Philippine Pesos (₱), conditions (MISB/Near Mint), and SKUs accurately based strictly on the Store Inventory below.
2. If an item is in stock, state the exact stock quantity (e.g., "We currently have 8 units in stock! 📦").
3. If an item is out of stock (stock = 0), explain politely and suggest checking back soon or recommend a related item.
4. If asked about shipping, state:
   - Flat standard delivery: ₱150 anywhere in Metro Manila and Provincial Philippines.
   - FREE Shipping for orders ₱2,500 and above!
   - Courier partners: J&T Express and LBC Express, with heavy-duty bubble armor and corner protectors.
5. If asked about payment methods:
   - We accept GCash, Maya, Credit/Debit cards (Visa/Mastercard), and Cash on Delivery (COD).
6. If asked about discounts or promo codes, share active promo codes from the Active Promotions list below (e.g. HOBBY10, GUNPLA20, FREESHIPPH) and explain what discount they provide.
7. You are an expert on Japanese Pokémon TCG sets and their exact English set equivalents. When customers ask about Japanese set codes (e.g. SV2a, SV8, S6a, SM12a), Japanese set names (e.g. Eevee Heroes, VSTAR Universe, Shiny Treasure ex), or what English set they correspond to, use the Pokémon Set Reference below to provide clear, expert collector guidance.
8. Be concise, friendly, engaging, and well-structured. Use short paragraphs and helpful bullet points. Use anime/gaming emojis tastefully (⚡, 🃏, 🤖, 📦, ✨, 🗡️).

CURRENT STORE INVENTORY & STOCKS:
{$catalogSummary}

ACTIVE PROMOTIONS & DISCOUNTS:
{$promoSummary}

POKÉMON JAPANESE-TO-ENGLISH SET REFERENCE:
{$pokemonSetSummary}
EOT;

        // Build Gemini contents array from conversation history
        $contents = [];

        foreach (array_slice($history, -6) as $turn) {
            $role = ($turn['role'] ?? 'user') === 'model' || ($turn['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => (string) ($turn['content'] ?? '')],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'maxOutputTokens' => 800,
            ],
        ];

        $lastError = null;
        $response = null;
        $successfulModel = $primaryModel;

        foreach ($models as $m) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key={$apiKey}";

            $response = Http::timeout(20)
                ->withoutVerifying()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                $successfulModel = $m;
                break;
            }

            $lastError = $response->json('error.message') ?? 'HTTP '.$response->status();
        }

        if (! $response || $response->failed()) {
            throw new \RuntimeException("Gemini call failed: {$lastError}");
        }

        $replyText = (string) $response->json('candidates.0.content.parts.0.text');

        if (empty(trim($replyText))) {
            throw new \RuntimeException('Empty response from Gemini.');
        }

        // Match products mentioned in the query or response
        $suggestedProducts = $this->matchProducts($message, $replyText, $products);

        return [
            'reply' => trim($replyText),
            'suggested_products' => $suggestedProducts,
            'suggested_actions' => $this->generateSuggestedActions($message, $suggestedProducts),
            'model_used' => $successfulModel,
        ];
    }

    /**
     * Fallback response engine using database query & keyword reasoning.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, PromoCode>  $promos
     * @param  Collection<int, RefPokemonSet>  $pokemonSets
     * @return array{
     *     reply: string,
     *     suggested_products: array<int, array<string, mixed>>,
     *     suggested_actions: array<int, string>,
     *     model_used: string
     * }
     */
    protected function generateLocalFallback(string $message, Collection $products, Collection $promos, Collection $pokemonSets): array
    {
        $lower = strtolower($message);
        $suggestedProducts = $this->matchProducts($message, '', $products);

        // Check for promo inquiries
        if (str_contains($lower, 'promo') || str_contains($lower, 'coupon') || str_contains($lower, 'discount') || str_contains($lower, 'code')) {
            $promoList = $promos->map(function ($p): string {
                $val = $p->type === 'percentage' ? "{$p->value}% OFF" : ($p->type === 'shipping' ? 'FREE Shipping' : "₱{$p->value} OFF");

                return "• **{$p->code}**: {$val} on your order";
            })->implode("\n");

            $reply = "Here are our active discount coupons for collectors today! 🎟️✨\n\n".
                ($promoList ?: "• **HOBBY10**: 10% OFF all items\n• **FREESHIPPH**: Free shipping on all orders").
                "\n\nYou can apply any of these promo codes during checkout!";

            return [
                'reply' => $reply,
                'suggested_products' => $suggestedProducts,
                'suggested_actions' => ['How do I get free shipping?', 'Check Pokémon stock', 'Show Gunpla kits'],
                'model_used' => 'local-catalog-engine',
            ];
        }

        // Check for shipping inquiries
        if (str_contains($lower, 'shipping') || str_contains($lower, 'delivery') || str_contains($lower, 'courier') || str_contains($lower, 'ship') || str_contains($lower, 'free ship')) {
            $reply = "🚚 **RLG Hobby Shop Shipping & Logistics:**\n\n".
                "• **Flat Rate Shipping:** ₱150 anywhere in Metro Manila and Provincial PH.\n".
                "• **FREE Shipping:** Available on all orders of **₱2,500 and above**!\n".
                "• **Courier Partners:** J&T Express and LBC Express with tracking numbers provided.\n".
                '• **Collector Packaging:** All booster boxes, figures, and cards are packed with bubble armor and corner guards to ensure 100% pristine arrival!';

            return [
                'reply' => $reply,
                'suggested_products' => $suggestedProducts,
                'suggested_actions' => ['What promo codes are active?', 'Check Pokémon TCG stock', 'Show One Piece booster box'],
                'model_used' => 'local-catalog-engine',
            ];
        }

        // Check for payment inquiries
        if (str_contains($lower, 'payment') || str_contains($lower, 'pay') || str_contains($lower, 'gcash') || str_contains($lower, 'maya') || str_contains($lower, 'cod')) {
            $reply = "💳 **Accepted Payment Methods:**\n\n".
                "• **GCash & Maya:** Direct digital wallet checkout.\n".
                "• **Cash on Delivery (COD):** Pay upon receiving your parcel from our courier.\n".
                "• **Credit & Debit Cards:** Visa and Mastercard processed securely.\n\n".
                'All orders are confirmed immediately with packing slips generated for fast dispatch! ⚡';

            return [
                'reply' => $reply,
                'suggested_products' => $suggestedProducts,
                'suggested_actions' => ['Check available products', 'What are shipping fees?'],
                'model_used' => 'local-catalog-engine',
            ];
        }

        // Check for Pokemon Japanese-to-English set reference inquiries
        $matchedPokemonSets = $this->matchPokemonSets($message, $pokemonSets);
        if ($matchedPokemonSets->isNotEmpty()) {
            $lines = [];
            foreach ($matchedPokemonSets->take(4) as $s) {
                $codeStr = $s->japanese_code ? " (`{$s->japanese_code}`)" : '';
                $notesStr = $s->notes ? "\n  - *Highlights:* {$s->notes}" : '';
                $lines[] = "• **{$s->japanese_set}**{$codeStr}\n  - **Series:** {$s->series} ({$s->series_years})\n  - **English Equivalent:** **{$s->english_set}** [{$s->set_type}]{$notesStr}";
            }

            $reply = "Here is the official Japanese-to-English Pokémon expansion mapping from our database reference! ⚡🃏\n\n".
                implode("\n\n", $lines).
                "\n\nWould you like me to check our current inventory for booster boxes, packs, or singles from this set?";

            return [
                'reply' => $reply,
                'suggested_products' => $suggestedProducts,
                'suggested_actions' => ['Check available Pokémon cards', 'Show Pokémon booster boxes', 'What promo codes are active?'],
                'model_used' => 'local-catalog-engine',
            ];
        }

        // Product search and stock inquiry
        if (! empty($suggestedProducts)) {
            $lines = [];
            foreach (array_slice($suggestedProducts, 0, 3) as $prod) {
                $stockStatus = $prod['stock'] > 0
                    ? "✅ In Stock ({$prod['stock']} units available)"
                    : '❌ Currently Out of Stock';

                $lines[] = "• **{$prod['name']}**\n  - SKU: `{$prod['sku']}`\n  - Price: ₱".number_format($prod['price'], 2)."\n  - Status: {$stockStatus}\n  - Condition: {$prod['condition']}";
            }

            $reply = "Here is what I found in our real-time store catalog! 📦✨\n\n".
                implode("\n\n", $lines).
                "\n\nWould you like more details or help adding any of these items to your cart?";

            return [
                'reply' => $reply,
                'suggested_products' => $suggestedProducts,
                'suggested_actions' => ['Tell me about shipping', 'Any discount codes?', 'View all Pokémon cards'],
                'model_used' => 'local-catalog-engine',
            ];
        }

        // General greeting / fallback
        $reply = "Konnichiwa! I'm **Aiko**, your RLG Hobby AI concierge! ⚡🃏\n\n".
            "I have real-time access to our entire catalog, current stocks on hand, and active promotions. You can ask me:\n".
            "• *\"Do you have Pokémon 151 Elite Trainer Boxes in stock?\"*\n".
            "• *\"How much is the One Piece OP-05 Booster Box?\"*\n".
            "• *\"What promo codes can I use today?\"*\n".
            "• *\"What are your shipping rates and free shipping minimum?\"*\n\n".
            'How can I help power up your collection today?';

        return [
            'reply' => $reply,
            'suggested_products' => $products->take(3)->map(fn ($p) => $this->formatProductItem($p))->values()->all(),
            'suggested_actions' => [
                '🃏 Check Pokémon TCG stock',
                '📦 Show One Piece boxes',
                '🎟️ Active Promo Codes',
                '🚚 Shipping rates',
            ],
            'model_used' => 'local-catalog-engine',
        ];
    }

    /**
     * Builds text summary of all store products and real-time stocks for the AI model.
     *
     * @param  Collection<int, Product>  $products
     */
    protected function buildCatalogContext(Collection $products): string
    {
        $lines = [];

        foreach ($products as $p) {
            $brand = $p->brand?->name ?? 'Import';
            $category = $p->category?->desc ?? 'Hobby';
            $condition = $p->condition?->desc ?? 'Brand New';
            $stockText = $p->stock > 0 ? "IN STOCK: {$p->stock} units available" : 'OUT OF STOCK';

            $lines[] = "- Product #{$p->id}: \"{$p->name}\" | SKU: {$p->sku} | Price: ₱{$p->price} | {$stockText} | Category: {$category} | Brand: {$brand} | Condition: {$condition}";
        }

        return implode("\n", $lines);
    }

    /**
     * Builds text summary of active promo codes for the AI model.
     *
     * @param  Collection<int, PromoCode>  $promos
     */
    protected function buildPromoContext(Collection $promos): string
    {
        if ($promos->isEmpty()) {
            return '- HOBBY10: 10% discount on entire order\n- FREESHIPPH: Free shipping promotion';
        }

        $lines = [];
        foreach ($promos as $promo) {
            $discount = match ($promo->type) {
                'percentage' => "{$promo->value}% OFF",
                'shipping' => 'FREE Shipping',
                default => "₱{$promo->value} OFF",
            };
            $lines[] = "- Code: \"{$promo->code}\" -> {$discount} (Status: Active)";
        }

        return implode("\n", $lines);
    }

    /**
     * Finds products in the catalog that are mentioned in the query or response text.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, array<string, mixed>>
     */
    protected function matchProducts(string $query, string $reply, Collection $products): array
    {
        $combinedText = strtolower($query.' '.$reply);
        $matches = [];

        foreach ($products as $p) {
            $nameLower = strtolower($p->name);
            $skuLower = strtolower((string) $p->sku);

            $matched = false;

            // Direct SKU match
            if (! empty($skuLower) && str_contains($combinedText, $skuLower)) {
                $matched = true;
            }

            // Keyword match on distinctive words
            if (! $matched) {
                $keywords = [
                    '151', 'charizard', 'one piece', 'op-05', 'hololive',
                    'gundam', 'rx-78', 'freedom', 'luffy', 'frieren',
                    'nendoroid', 'leafeon', 'abomasnow', 'shiny star',
                ];

                foreach ($keywords as $kw) {
                    if (str_contains($combinedText, $kw) && (str_contains($nameLower, $kw) || str_contains($skuLower, $kw))) {
                        $matched = true;
                        break;
                    }
                }
            }

            // Partial name match if at least 2 significant words match
            if (! $matched) {
                $words = array_filter(explode(' ', $nameLower), fn ($w) => strlen($w) > 3);
                $wordMatches = 0;
                foreach ($words as $w) {
                    if (str_contains($combinedText, $w)) {
                        $wordMatches++;
                    }
                }
                if ($wordMatches >= 2) {
                    $matched = true;
                }
            }

            if ($matched) {
                $matches[] = $this->formatProductItem($p);
            }
        }

        return array_slice($matches, 0, 4);
    }

    /**
     * Format a product model into a frontend-ready structure for the chatbot card.
     *
     * @return array<string, mixed>
     */
    protected function formatProductItem(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
            'stock' => (int) $p->stock,
            'in_stock' => $p->stock > 0,
            'image_url' => $p->image_url ?: 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=500&auto=format&fit=crop&q=80',
            'brand' => $p->brand?->name ?? 'Authentic Japan',
            'category' => $p->category?->desc ?? 'Collectibles',
            'condition' => $p->condition?->desc ?? 'Brand New',
            'rating' => (float) ($p->rating ?? 5.0),
        ];
    }

    /**
     * Generate dynamic follow-up prompt suggestions based on the conversation context.
     *
     * @param  array<int, array<string, mixed>>  $matchedProducts
     * @return array<int, string>
     */
    protected function generateSuggestedActions(string $message, array $matchedProducts): array
    {
        $lower = strtolower($message);

        if (! empty($matchedProducts)) {
            $firstName = $matchedProducts[0]['name'] ?? 'this item';
            $shortName = strlen($firstName) > 25 ? substr($firstName, 0, 22).'...' : $firstName;

            return [
                "Is {$shortName} brand new?",
                'What are your shipping rates?',
                'Any active discount promo codes?',
            ];
        }

        if (str_contains($lower, 'shipping') || str_contains($lower, 'delivery')) {
            return [
                'What promo codes are active?',
                'Check Pokémon 151 stock',
                'Show One Piece OP-05 booster box',
            ];
        }

        if (str_contains($lower, 'promo') || str_contains($lower, 'discount')) {
            return [
                'How do I get free shipping?',
                'Check Gunpla model kits',
                'Show in-stock Anime figures',
            ];
        }

        return [
            '🃏 Check Pokémon TCG stock',
            '📦 Show One Piece boxes',
            '🤖 Gunpla kits available',
            '🚚 Shipping fees & free delivery',
        ];
    }

    /**
     * Builds text summary of Pokemon Japanese-to-English set mappings for the AI prompt.
     *
     * @param  Collection<int, RefPokemonSet>  $pokemonSets
     */
    protected function buildPokemonSetContext(Collection $pokemonSets): string
    {
        if ($pokemonSets->isEmpty()) {
            return "- SV1a (Triplet Beat) -> English: Paldea Evolved\n- SV2a (Pokémon Card 151) -> English: Scarlet & Violet—151\n- S6a (Eevee Heroes) -> English: Evolving Skies\n- S12a (VSTAR Universe) -> English: Crown Zenith";
        }

        $lines = [];
        foreach ($pokemonSets as $s) {
            $code = $s->japanese_code ? " [Code: {$s->japanese_code}]" : '';
            $notes = $s->notes ? " ({$s->notes})" : '';
            $lines[] = "- {$s->series} | JP: \"{$s->japanese_set}\"{$code} ===> EN: \"{$s->english_set}\" [{$s->set_type}]{$notes}";
        }

        return implode("\n", $lines);
    }

    /**
     * Matches Pokemon sets from a user query by code or set title.
     *
     * @param  Collection<int, RefPokemonSet>  $pokemonSets
     * @return Collection<int, RefPokemonSet>
     */
    protected function matchPokemonSets(string $message, Collection $pokemonSets): Collection
    {
        $lower = strtolower($message);

        // 1. Regex check for set codes (SV2a, S6a, SV8, etc.)
        if (preg_match_all('/\b(SV\d+[a-z]?|S\d+[a-z]?|SM\d+[a-z]?|XY\d*|BW\d*|CP\d*)\b/i', $message, $matches)) {
            $codes = array_map('strtoupper', $matches[0]);
            $found = $pokemonSets->filter(function ($s) use ($codes) {
                if (! $s->japanese_code) {
                    return false;
                }
                foreach ($codes as $c) {
                    if (str_contains(strtoupper($s->japanese_code), $c)) {
                        return true;
                    }
                }

                return false;
            });

            if ($found->isNotEmpty()) {
                return $found;
            }
        }

        // 2. Named set checks
        $matched = $pokemonSets->filter(function ($s) use ($lower) {
            $jpLower = strtolower($s->japanese_set);
            $enLower = strtolower($s->english_set);

            $checkNames = [
                '151', 'eevee heroes', 'vstar universe', 'shiny treasure', 'clay burst',
                'snow hazard', 'triplet beat', 'raging surf', 'stellar miracle', 'paradise dragona',
                'super electric breaker', 'dream league', 'tag all stars', 'vmax climax', 'shiny star v',
                'evolving skies', 'paldea evolved', 'crown zenith', 'lost origin', 'fusion strike',
                'twilight masquerade', 'shrouded fable', 'surging sparks', 'stellar crown', 'temporal forces',
            ];

            foreach ($checkNames as $name) {
                if (str_contains($lower, $name)) {
                    if (str_contains($jpLower, $name) || str_contains($enLower, $name)) {
                        return true;
                    }
                }
            }

            return false;
        });

        if ($matched->isNotEmpty()) {
            return $matched;
        }

        // 3. General inquiry about pokemon sets or japanese sets
        if ((str_contains($lower, 'pokemon') || str_contains($lower, 'tcg')) && (str_contains($lower, 'set') || str_contains($lower, 'japanese') || str_contains($lower, 'equivalent') || str_contains($lower, 'expansion'))) {
            return $pokemonSets->where('series', 'Scarlet & Violet')->take(5);
        }

        return collect();
    }
}
