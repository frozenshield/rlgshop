<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefPokemonSet;
use App\Models\RefSubcategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PopulateProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:populate {count=500 : Number of products to generate (default 500)} {--truncate : Clear existing products first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-populate the catalog with authentic, realistic hobby store products (Pokemon, One Piece, Gunpla, Figures, Merch)';

    /**
     * Curated authentic high-resolution images for each category
     */
    protected array $imagePool = [
        'pokemon' => [
            'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=800&auto=format&fit=crop&q=80',
        ],
        'onepiece' => [
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80',
        ],
        'tcg' => [
            'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=800&auto=format&fit=crop&q=80',
        ],
        'gunpla' => [
            'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1608889825205-eebdb9fc5806?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80',
        ],
        'figures' => [
            'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=800&auto=format&fit=crop&q=80',
        ],
        'merch' => [
            'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1582562124811-c09040d0a901?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1608889825205-eebdb9fc5806?w=800&auto=format&fit=crop&q=80',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetCount = (int) $this->argument('count');
        if ($targetCount <= 0) {
            $targetCount = 500;
        }

        if ($this->option('truncate')) {
            $this->warn('Truncating existing products table...');
            Product::truncate();
        }

        $this->info("Generating {$targetCount} authentic hobby shop products...");

        // Ensure reference records exist
        $brands = RefBrand::pluck('id', 'name')->toArray();
        $categories = RefCategory::with('subcategories')->get()->keyBy('desc');
        $conditions = RefCondition::pluck('id', 'desc')->toArray();
        $pokemonSets = RefPokemonSet::all();

        // Fallbacks
        $defaultBrandId = reset($brands) ?: 1;
        $defaultConditionId = $conditions['MISB'] ?? (reset($conditions) ?: 1);

        $templates = $this->buildTemplates();
        $totalTemplates = count($templates);

        $created = 0;
        $existingSkus = Product::pluck('sku')->flip()->toArray();

        $bar = $this->output->createProgressBar($targetCount);
        $bar->start();

        while ($created < $targetCount) {
            // Pick a template
            $templateIndex = $created % $totalTemplates;
            $template = $templates[$templateIndex];

            // Add variant if we loop over templates more than once
            $loopCycle = (int) floor($created / $totalTemplates);
            $item = $this->instantiateProduct($template, $loopCycle, $brands, $categories, $conditions, $pokemonSets, $defaultBrandId, $defaultConditionId, $existingSkus);

            Product::create($item);
            $existingSkus[$item['sku']] = true;
            $created++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Successfully populated {$created} authentic products into the database!");

        return Command::SUCCESS;
    }

    protected function instantiateProduct(
        array $template,
        int $cycle,
        array $brands,
        $categories,
        array $conditions,
        $pokemonSets,
        int $defaultBrandId,
        int $defaultConditionId,
        array $existingSkus
    ): array {
        $name = $template['name'];
        if ($cycle > 0) {
            $variantSuffixes = ['[Restock Wave ' . ($cycle + 1) . ']', '[Collector Special Pack]', '[Japanese Import Edition]', '[Limited First Print]', '[Display Box Bundle]'];
            $name .= ' ' . $variantSuffixes[$cycle % count($variantSuffixes)];
        }

        // Generate base SKU
        $baseSku = $template['sku'];
        $sku = $cycle > 0 ? "{$baseSku}-V" . ($cycle + 1) : $baseSku;
        if (isset($existingSkus[$sku])) {
            $sku = "{$sku}-" . strtoupper(Str::random(3));
        }

        // Resolve brand
        $brandId = $brands[$template['brand']] ?? $defaultBrandId;

        // Resolve category & subcategory
        $catObj = $categories->get($template['category']);
        $catId = $catObj?->id ?? 1;
        $subId = null;
        if ($catObj && !empty($template['subcategory'])) {
            $subObj = $catObj->subcategories->firstWhere('desc', $template['subcategory']);
            $subId = $subObj?->id;
        }

        // Resolve condition
        $condId = $conditions[$template['condition'] ?? 'MISB'] ?? $defaultConditionId;

        // Resolve Pokemon set if applicable
        $pokemonSetId = null;
        if (!empty($template['pokemon_code'])) {
            $set = $pokemonSets->firstWhere('japanese_code', $template['pokemon_code']);
            $pokemonSetId = $set?->id;
        }

        // Realistic variations
        $priceJitter = ($cycle * 50) + rand(-100, 150);
        $price = max(199, (float) ($template['price'] + $priceJitter));
        $stock = rand(3, 48);
        $rating = round(4.50 + (rand(0, 49) / 100), 2);
        $reviewCount = rand(5, 85);

        // Pick matching image
        $imageKey = $template['image_type'] ?? 'tcg';
        $images = $this->imagePool[$imageKey] ?? $this->imagePool['tcg'];
        $imgUrl = $images[rand(0, count($images) - 1)];

        return [
            'name' => $name,
            'sku' => $sku,
            'stock' => $stock,
            'price' => $price,
            'description' => $template['description'],
            'ref_brand_id' => $brandId,
            'ref_category_id' => $catId,
            'ref_subcategory_id' => $subId,
            'ref_condition_id' => $condId,
            'ref_pokemon_set_id' => $pokemonSetId,
            'condition_id' => $condId,
            'weight' => (float) ($template['weight'] ?? 350.00),
            'length' => (float) ($template['length'] ?? 14.00),
            'width' => (float) ($template['width'] ?? 14.00),
            'height' => (float) ($template['height'] ?? 4.00),
            'status' => 'active',
            'rating' => $rating,
            'review_count' => $reviewCount,
            'image_url' => $imgUrl,
            'gallery_images' => array_slice($images, 0, 3),
        ];
    }

    /**
     * Broad library of ~100 distinct templates spanning all 4 catalog categories
     */
    protected function buildTemplates(): array
    {
        return [
            // ==========================================
            // POKÉMON TCG (Booster Boxes, ETBs, Slabs, Promos)
            // ==========================================
            [
                'name' => 'Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box',
                'sku' => 'TCG-PKM-151-ETB',
                'price' => 3199.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'SV2a',
                'weight' => 750,
                'length' => 19,
                'width' => 9,
                'height' => 17,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Official Pokémon TCG Scarlet & Violet 151 Elite Trainer Box featuring iconic original Kanto Pokémon.\n\nCONTENTS\n\t• 9 Pokémon TCG: Scarlet & Violet 151 booster packs\n\t• 1 full-art foil promo card featuring Snorlax illustration rare\n\t• 65 card sleeves featuring 151 logo design\n\t• 45 Energy cards, 6 damage-counter dice, 1 competition coin-flip die\n\t• 2 acrylic condition markers and collector storage box with 4 dividers",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: Terastal Festival ex Booster Box [SV8a]',
                'sku' => 'TCG-PKM-SV8A-BOX',
                'price' => 4650.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'SV8a',
                'weight' => 320,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Factory-sealed Japanese High-Class Booster Box for Terastal Festival ex (SV8a).\n\nFEATURES\n\t• 10 booster packs per box, 10 cards per pack\n\t• Guaranteed Pokémon ex or higher rarity in every single pack\n\t• Features Eevee evolutions and prismatic Tera Pokémon ex with special holographic borders",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: Super Electric Breaker Booster Box [SV8]',
                'sku' => 'TCG-PKM-SV8-BOX',
                'price' => 2950.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'SV8',
                'weight' => 300,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Japanese booster box featuring Stellar Tera Pikachu ex with devastating Topaz Bolt attack.\n\nSPECS\n\t• 30 booster packs per box, 5 cards per pack\n\t• Full factory shrink wrap intact with official Pokémon watermark seal",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: Paradise Dragona Booster Box [SV7a]',
                'sku' => 'TCG-PKM-SV7A-BOX',
                'price' => 2850.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'SV7a',
                'weight' => 300,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Japanese expansion highlighting Dragon-type masters: Alolan Exeggutor ex, Archaludon ex, and Latias ex.\n\nSPECS\n\t• 30 booster packs, 5 cards per pack, factory shrink wrap sealed",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: Stellar Miracle Booster Box [SV7]',
                'sku' => 'TCG-PKM-SV7-BOX',
                'price' => 2750.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'SV7',
                'weight' => 300,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Japanese expansion featuring Terapagos ex and the introductory mechanics for Stellar Crown.\n\nSPECS\n\t• 30 packs per box, authentic Japanese Pokémon Center print",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: Shiny Treasure ex High Class Pack [SV4a]',
                'sku' => 'TCG-PKM-SV4A-BOX',
                'price' => 3950.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'SV4a',
                'weight' => 340,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Japanese High Class Pack featuring Shiny Charizard ex, Shiny Mew ex, and SSR Baby Shinies.\n\nSPECS\n\t• 10 booster packs per box, 10 cards per pack with mirror foil variants",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: VSTAR Universe Booster Box [S12a]',
                'sku' => 'TCG-PKM-S12A-BOX',
                'price' => 5400.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'S12a',
                'weight' => 350,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Pinnacle Sword & Shield High-Class pack featuring Art Rare (AR) and Special Art Rare (SAR) cards including the iconic 9-card God Pack creation quartet.\n\nSPECS\n\t• 10 booster packs, 10 cards per pack",
            ],
            [
                'name' => 'Pokémon Card Game Japanese: Eevee Heroes Booster Box [S6a]',
                'sku' => 'TCG-PKM-S6A-BOX',
                'price' => 24500.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'MISB',
                'pokemon_code' => 'S6a',
                'weight' => 300,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Holy grail Japanese modern Pokémon booster box featuring Moonbreon (Umbreon VMAX Alt Art), Sylveon VMAX, and all Eeveelution alternate arts.\n\nSPECS\n\t• 30 packs per box, factory sealed with original tamper-evident Japanese pull tab and shrink",
            ],
            [
                'name' => 'PSA 10 Gem Mint: Charizard ex Special Illustration Rare [151 SV2a #201/165]',
                'sku' => 'SLB-PKM-151-CHZ-PSA10',
                'price' => 14999.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'Near Mint',
                'pokemon_code' => 'SV2a',
                'weight' => 80,
                'length' => 14,
                'width' => 8,
                'height' => 1,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Professionally graded PSA 10 Gem Mint Japanese Charizard ex Special Illustration Rare from Pokémon Card 151.\n\nCERTIFICATION\n\t• Certified genuine by Professional Sports Authenticator (PSA) in tamper-proof sonic weld acrylic holder",
            ],
            [
                'name' => 'PSA 10 Gem Mint: Pikachu Illustration Rare [Crown Zenith #160/159]',
                'sku' => 'SLB-PKM-CRZ-PIKA-PSA10',
                'price' => 7800.00,
                'brand' => 'The Pokémon Company',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Pokemon',
                'condition' => 'Near Mint',
                'weight' => 80,
                'length' => 14,
                'width' => 8,
                'height' => 1,
                'image_type' => 'pokemon',
                'description' => "OVERVIEW\n\t• Ultra rare secret illustration Pikachu card from Crown Zenith featuring all generations of partner Pokémon.\n\nGRADE\n\t• PSA 10 Gem Mint with pristine centering, sharp corners, and immaculate surface",
            ],

            // ==========================================
            // ONE PIECE CARD GAME (OP-01 to OP-09 & Starter Decks)
            // ==========================================
            [
                'name' => 'One Piece Card Game: OP-05 Awakening of the New Era Booster Box',
                'sku' => 'TCG-OP-05-BOX',
                'price' => 4850.00,
                'brand' => 'Bandai',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'One Piece',
                'condition' => 'MISB',
                'weight' => 380,
                'length' => 15,
                'width' => 14,
                'height' => 4,
                'image_type' => 'onepiece',
                'description' => "OVERVIEW\n\t• 1st Anniversary One Piece Card Game expansion box featuring Gear 5 Sun God Luffy Manga Super Parallel rare.\n\nCONTENTS\n\t• 24 booster packs per box, 6 cards per pack (English edition)",
            ],
            [
                'name' => 'One Piece Card Game: OP-09 The Four Emperors Booster Box',
                'sku' => 'TCG-OP-09-BOX',
                'price' => 4950.00,
                'brand' => 'Bandai',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'One Piece',
                'condition' => 'MISB',
                'weight' => 380,
                'length' => 15,
                'width' => 14,
                'height' => 4,
                'image_type' => 'onepiece',
                'description' => "OVERVIEW\n\t• Celebrates the 2nd Anniversary featuring all Four Emperors: Shanks, Buggy, Blackbeard, and Luffy!\n\nSPECS\n\t• 24 packs per box, sealed in official Bandai blue tape shrink wrap",
            ],
            [
                'name' => 'One Piece Card Game: OP-07 500 Years in the Future Booster Box',
                'sku' => 'TCG-OP-07-BOX',
                'price' => 4450.00,
                'brand' => 'Bandai',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'One Piece',
                'condition' => 'MISB',
                'weight' => 380,
                'length' => 15,
                'width' => 14,
                'height' => 4,
                'image_type' => 'onepiece',
                'description' => "OVERVIEW\n\t• Focuses on the Egghead Arc with characters like Vegapunk, Jewelry Bonney, and the Revolutionary Army.\n\nSPECS\n\t• 24 packs per box, English factory sealed",
            ],
            [
                'name' => 'One Piece Card Game: OP-01 Romance Dawn Booster Box [Re-wave]',
                'sku' => 'TCG-OP-01-BOX',
                'price' => 8900.00,
                'brand' => 'Bandai',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'One Piece',
                'condition' => 'MISB',
                'weight' => 380,
                'length' => 15,
                'width' => 14,
                'height' => 4,
                'image_type' => 'onepiece',
                'description' => "OVERVIEW\n\t• The legendary debut set of the One Piece Card Game featuring Manga Shanks Super Parallel Rare.\n\nSPECS\n\t• 24 packs per box, official English print",
            ],
            [
                'name' => 'One Piece Card Game: ST-14 3D2Y Straw Hat Crew Starter Deck',
                'sku' => 'TCG-OP-ST14-DECK',
                'price' => 750.00,
                'brand' => 'Bandai',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'One Piece',
                'condition' => 'MISB',
                'weight' => 140,
                'length' => 10,
                'width' => 7,
                'height' => 3,
                'image_type' => 'onepiece',
                'description' => "OVERVIEW\n\t• Ready-to-play 50-card constructed deck, 1 Leader card, 10 DON!! cards, and playsheet focusing on Straw Hat Crew training arc.",
            ],
            [
                'name' => 'One Piece Card Game: Premium Card Collection - Best Selection Vol.2',
                'sku' => 'TCG-OP-PRM-VOL2',
                'price' => 1850.00,
                'brand' => 'Bandai',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'One Piece',
                'condition' => 'MISB',
                'weight' => 200,
                'length' => 30,
                'width' => 21,
                'height' => 1,
                'image_type' => 'onepiece',
                'description' => "OVERVIEW\n\t• Premium folder containing 12 exclusive alternate art tournament-staple cards with new foil stamp embossing.",
            ],

            // ==========================================
            // YU-GI-OH! & WEISS SCHWARZ & HOLOLIVE
            // ==========================================
            [
                'name' => 'Yu-Gi-Oh! TCG: 25th Anniversary Rarity Collection II Booster Box',
                'sku' => 'TCG-YGO-RC02-BOX',
                'price' => 3850.00,
                'brand' => 'Konami',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Yugioh',
                'condition' => 'MISB',
                'weight' => 340,
                'length' => 14,
                'width' => 12,
                'height' => 4,
                'image_type' => 'tcg',
                'description' => "OVERVIEW\n\t• All-foil booster box featuring 7 different luxury rarities including Quarter Century Secret Rare, Collector's Rare, and Ultimate Rare.",
            ],
            [
                'name' => 'Yu-Gi-Oh! TCG: Quarter Century Bonanza Booster Box',
                'sku' => 'TCG-YGO-QCB-BOX',
                'price' => 4200.00,
                'brand' => 'Konami',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Yugioh',
                'condition' => 'MISB',
                'weight' => 350,
                'length' => 14,
                'width' => 12,
                'height' => 4,
                'image_type' => 'tcg',
                'description' => "OVERVIEW\n\t• 24 packs per box with 5 cards per pack featuring classic anime cards and competitive tournament staples in Quarter Century Secret rarity.",
            ],
            [
                'name' => 'Hololive Official Card Game: Blooming Radiance Booster Box [hBP01]',
                'sku' => 'TCG-HOLO-BP01',
                'price' => 3950.00,
                'brand' => 'Bushiroad',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Hololive',
                'condition' => 'MISB',
                'weight' => 380,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'tcg',
                'description' => "OVERVIEW\n\t• Official Hololive card game debut booster box featuring generation talents, stage illustrations, and sign autograph cards.",
            ],
            [
                'name' => 'Weiß Schwarz: Frieren: Beyond Journey\'s End Booster Box',
                'sku' => 'TCG-WS-FRIEREN-BOX',
                'price' => 3650.00,
                'brand' => 'Bushiroad',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Weiss Schwarz',
                'condition' => 'MISB',
                'weight' => 320,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'tcg',
                'description' => "OVERVIEW\n\t• 16 booster packs per box featuring Frieren, Fern, Stark, and Himmel voice actor foil signatures (SP/SEC).",
            ],
            [
                'name' => 'Weiß Schwarz: Bocchi the Rock! Booster Box',
                'sku' => 'TCG-WS-BOCCHI-BOX',
                'price' => 3800.00,
                'brand' => 'Bushiroad',
                'category' => 'TCG (Trading Cards)',
                'subcategory' => 'Weiss Schwarz',
                'condition' => 'MISB',
                'weight' => 320,
                'length' => 14,
                'width' => 14,
                'height' => 4,
                'image_type' => 'tcg',
                'description' => "OVERVIEW\n\t• Kessoku Band cards with original anime cutscene artwork and hot stamped cast signatures.",
            ],

            // ==========================================
            // GUNPLA MODEL KITS (PG, MG, RG, HG, EG)
            // ==========================================
            [
                'name' => 'PG Unleashed 1/60 RX-78-2 Gundam Model Kit',
                'sku' => 'GUN-PGU-RX78-2',
                'price' => 15500.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Perfect Grade (PG)',
                'condition' => 'MISB',
                'weight' => 2400,
                'length' => 59,
                'width' => 39,
                'height' => 17,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• The pinnacle of Bandai Gunpla engineering with a multi-layered inner frame, etched metallic parts, 3D metallic stickers, and comprehensive dual-color LED unit system.",
            ],
            [
                'name' => 'PG 1/60 Strike Freedom Gundam Model Kit',
                'sku' => 'GUN-PG-STRIKE-FREEDOM',
                'price' => 14800.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Perfect Grade (PG)',
                'condition' => 'MISB',
                'weight' => 2200,
                'length' => 59,
                'width' => 39,
                'height' => 16,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Massive 1/60 scale Strike Freedom with extending Super DRAGOON wing binders, gold-plated internal frame parts, and beam shield.",
            ],
            [
                'name' => 'MG 1/100 Freedom Gundam Ver.2.0 Model Kit',
                'sku' => 'GUN-MG-FREEDOM-V2',
                'price' => 3200.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Master Grade (MG)',
                'condition' => 'MISB',
                'weight' => 880,
                'length' => 39,
                'width' => 31,
                'height' => 9,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Revamped Master Grade kit featuring dynamic wing articulation, high-mobility hip joints, Lupus beam rifle, and Lacerta beam sabers.",
            ],
            [
                'name' => 'MG 1/100 Gundam Barbatos Model Kit',
                'sku' => 'GUN-MG-BARBATOS',
                'price' => 3150.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Master Grade (MG)',
                'condition' => 'MISB',
                'weight' => 820,
                'length' => 39,
                'width' => 31,
                'height' => 8,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Fully realized Gundam Frame with working silver-plated hydraulic cylinders, heavy mace, smoothbore gun, and long sword.",
            ],
            [
                'name' => 'MG 1/100 Nu Gundam Ver.Ka Model Kit',
                'sku' => 'GUN-MG-NUGUNDAM-KA',
                'price' => 4500.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Master Grade (MG)',
                'condition' => 'MISB',
                'weight' => 1100,
                'length' => 39,
                'width' => 31,
                'height' => 11,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Designed by master mecha artist Hajime Katoki. Features expanding armor hatches revealing green Psycho-Frame underneath, 6 Fin Funnels, and dedicated stand.",
            ],
            [
                'name' => 'MG 1/100 Sazabi Ver.Ka Model Kit',
                'sku' => 'GUN-MG-SAZABI-KA',
                'price' => 5800.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Master Grade (MG)',
                'condition' => 'MISB',
                'weight' => 1650,
                'length' => 59,
                'width' => 39,
                'height' => 11,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Colossal Master Grade kit with intricate opening thruster panels, silver hydraulic details, beam shot rifle, beam tomahawk, and silver-decaled shield.",
            ],
            [
                'name' => 'RG 1/144 RX-78-2 Gundam Ver.2.0 Model Kit',
                'sku' => 'GUN-RG-RX78-2-V2',
                'price' => 2550.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Real Grade (RG)',
                'condition' => 'MISB',
                'weight' => 520,
                'length' => 30,
                'width' => 19,
                'height' => 7,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Revolutionary new inner frame engineering commemorating the 45th anniversary of Mobile Suit Gundam. Core Fighter transforms and docks seamlessly.",
            ],
            [
                'name' => 'RG 1/144 Hi-Nu Gundam Model Kit',
                'sku' => 'GUN-RG-HINU',
                'price' => 2950.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Real Grade (RG)',
                'condition' => 'MISB',
                'weight' => 580,
                'length' => 31,
                'width' => 20,
                'height' => 10,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Widely acclaimed as the best RG kit ever made. Unmatched articulation, posable Fin Funnel wing binders, and mechanical armor separation.",
            ],
            [
                'name' => 'RG 1/144 God Gundam Model Kit',
                'sku' => 'GUN-RG-GODGUNDAM',
                'price' => 2350.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'Real Grade (RG)',
                'condition' => 'MISB',
                'weight' => 450,
                'length' => 30,
                'width' => 19,
                'height' => 7,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Designed for extreme martial arts posing with human-like shoulder joints, multi-segmented torso, and 3-stage effect ring.",
            ],
            [
                'name' => 'HG 1/144 Gundam Calibarn Model Kit',
                'sku' => 'GUN-HG-CALIBARN',
                'price' => 1350.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'High Grade (HG)',
                'condition' => 'MISB',
                'weight' => 380,
                'length' => 30,
                'width' => 19,
                'height' => 6,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• The climactic suit from The Witch from Mercury with rainbow iridescent in-mold chest parts and broom-like Variable Rod Rifle.",
            ],
            [
                'name' => 'HG 1/144 Gundam Aerial Model Kit',
                'sku' => 'GUN-HG-AERIAL',
                'price' => 1100.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'High Grade (HG)',
                'condition' => 'MISB',
                'weight' => 320,
                'length' => 30,
                'width' => 19,
                'height' => 5,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• Features 11 GUND-Bit staves that combine into the Escutcheon shield or attach to the armor as bit-on form.",
            ],
            [
                'name' => 'HG 1/144 Mighty Strike Freedom Gundam Model Kit',
                'sku' => 'GUN-HG-MIGHTY-SF',
                'price' => 1750.00,
                'brand' => 'Bandai Spirits',
                'category' => 'Gunpla',
                'subcategory' => 'High Grade (HG)',
                'condition' => 'MISB',
                'weight' => 420,
                'length' => 30,
                'width' => 19,
                'height' => 7,
                'image_type' => 'gunpla',
                'description' => "OVERVIEW\n\t• From Mobile Suit Gundam SEED FREEDOM, equipped with the Proud Defender backpack and physical katana 'Futsunomitama'.",
            ],

            // ==========================================
            // ANIME FIGURES & NENDOROIDS & POP UP PARADE
            // ==========================================
            [
                'name' => 'Nendoroid Frieren (#2280) - Frieren: Beyond Journey\'s End',
                'sku' => 'FIG-NEN-FRIEREN-2280',
                'price' => 2850.00,
                'brand' => 'Good Smile Company',
                'category' => 'Anime Figures',
                'subcategory' => 'Nendoroids',
                'condition' => 'MISB',
                'weight' => 280,
                'length' => 18,
                'width' => 14,
                'height' => 9,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Authentic Good Smile Company Nendoroid of the mage Frieren with staff, grimoire, blue-moon weed flower, and signature smug faceplate.",
            ],
            [
                'name' => 'Nendoroid Fern (#2305) - Frieren: Beyond Journey\'s End',
                'sku' => 'FIG-NEN-FERN-2305',
                'price' => 2750.00,
                'brand' => 'Good Smile Company',
                'category' => 'Anime Figures',
                'subcategory' => 'Nendoroids',
                'condition' => 'MISB',
                'weight' => 280,
                'length' => 18,
                'width' => 14,
                'height' => 9,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Includes Fern's mage staff, Zoltraak offensive magic beam effect part, hair ornament, and pouty expression faceplate.",
            ],
            [
                'name' => 'Nendoroid Gojo Satoru: Jujutsu Kaisen 0 Ver. (#1528)',
                'sku' => 'FIG-NEN-GOJO-1528',
                'price' => 3200.00,
                'brand' => 'Good Smile Company',
                'category' => 'Anime Figures',
                'subcategory' => 'Nendoroids',
                'condition' => 'MISB',
                'weight' => 290,
                'length' => 18,
                'width' => 14,
                'height' => 9,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Includes hair parts with blindfold on, removable blindfold hair parts, sunglasses, and hand parts posing for Hollow Purple.",
            ],
            [
                'name' => 'Nendoroid Anya Forger (#1900) - SPY x FAMILY',
                'sku' => 'FIG-NEN-ANYA-1900',
                'price' => 2950.00,
                'brand' => 'Good Smile Company',
                'category' => 'Anime Figures',
                'subcategory' => 'Nendoroids',
                'condition' => 'MISB',
                'weight' => 260,
                'length' => 18,
                'width' => 14,
                'height' => 9,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Comes with Eden Academy uniform, Chimera doll, bag of peanuts, and her legendary 'heh' smug expression faceplate.",
            ],
            [
                'name' => 'Nendoroid Hitori Gotoh (Bocchi) (#2060) - Bocchi the Rock!',
                'sku' => 'FIG-NEN-BOCCHI-2060',
                'price' => 3100.00,
                'brand' => 'Good Smile Company',
                'category' => 'Anime Figures',
                'subcategory' => 'Nendoroids',
                'condition' => 'MISB',
                'weight' => 270,
                'length' => 18,
                'width' => 14,
                'height' => 9,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Features Bocchi with Gibson Les Paul guitar, guitar case, glitch meltdown melting faceplate, and anxiety trash-can box.",
            ],
            [
                'name' => 'Pop Up Parade L: Guts (Berserker Armor) - Berserk',
                'sku' => 'FIG-PUP-GUTS-L',
                'price' => 4500.00,
                'brand' => 'Good Smile Company',
                'category' => 'Anime Figures',
                'subcategory' => 'Pop Up Parade',
                'condition' => 'MISB',
                'weight' => 920,
                'length' => 32,
                'width' => 22,
                'height' => 18,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Imposing 28cm tall Large-size Pop Up Parade figure featuring Guts wielding the Dragonslayer in full Berserker Armor posture.",
            ],
            [
                'name' => 'Monkey D. Luffy Gear 5 King of Artist - One Piece',
                'sku' => 'FIG-KOA-LUFFY-G5',
                'price' => 1450.00,
                'brand' => 'Banpresto',
                'category' => 'Anime Figures',
                'subcategory' => 'Prize Figures',
                'condition' => 'MISB',
                'weight' => 520,
                'length' => 20,
                'width' => 15,
                'height' => 10,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Premium sculpted 16cm figure from Banpresto's flagship King of Artist line capturing Luffy laughing heartily in Sun God Nika Gear 5 form.",
            ],
            [
                'name' => 'Roronoa Zoro: The Shukkei - One Piece Wano Arc Figure',
                'sku' => 'FIG-BAN-ZORO-WANO',
                'price' => 1350.00,
                'brand' => 'Banpresto',
                'category' => 'Anime Figures',
                'subcategory' => 'Prize Figures',
                'condition' => 'MISB',
                'weight' => 480,
                'length' => 20,
                'width' => 15,
                'height' => 10,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Detailed 18cm figure of Zoro in black wano coat drawing Enma with dynamic wind-blown haori cloth sculpting.",
            ],
            [
                'name' => 'ARTFX J 1/8 Scale: Levi Ackerman Fortitude Ver. - Attack on Titan',
                'sku' => 'FIG-KTB-LEVI-FORT',
                'price' => 9800.00,
                'brand' => 'Kotobukiya',
                'category' => 'Anime Figures',
                'subcategory' => 'Scale Figures',
                'condition' => 'MISB',
                'weight' => 1200,
                'length' => 34,
                'width' => 25,
                'height' => 22,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Masterpiece 1/8 scale statue depicting Captain Levi battle-damaged amidst giant forest tree trunks with dual ultra-hard steel blades drawn.",
            ],
            [
                'name' => 'LookUp: Satoru Gojo & Suguru Geto Suit Ver. Set - Jujutsu Kaisen',
                'sku' => 'FIG-MH-LOOKUP-GOJO-GETO',
                'price' => 3800.00,
                'brand' => 'MegaHouse',
                'category' => 'Anime Figures',
                'subcategory' => 'Nendoroids',
                'condition' => 'MISB',
                'weight' => 360,
                'length' => 20,
                'width' => 12,
                'height' => 12,
                'image_type' => 'figures',
                'description' => "OVERVIEW\n\t• Set of two 11cm LookUp figures with movable tilt-up heads designed to gaze right at you on your desk or computer monitor.",
            ],

            // ==========================================
            // CARD ACCESSORIES & COLLECTIBLES SUPPLIES
            // ==========================================
            [
                'name' => 'Dragon Shield Matte Card Sleeves - Jet Black (100ct Standard)',
                'sku' => 'ACC-DS-MATTE-JET',
                'price' => 650.00,
                'brand' => 'Bushiroad',
                'category' => 'Anime Merch Collectibles',
                'subcategory' => 'Stationery & Clear Files',
                'condition' => 'MISB',
                'weight' => 160,
                'length' => 10,
                'width' => 7,
                'height' => 4,
                'image_type' => 'merch',
                'description' => "OVERVIEW\n\t• World standard 120-micron textured matte back gaming sleeves. Acid-free, no PVC, fits standard Pokémon, Magic, and One Piece cards.",
            ],
            [
                'name' => 'KMC Perfect Size Inner Sleeves (100ct Standard Clear)',
                'sku' => 'ACC-KMC-PERFECT-SIZE',
                'price' => 220.00,
                'brand' => 'Bushiroad',
                'category' => 'Anime Merch Collectibles',
                'subcategory' => 'Stationery & Clear Files',
                'condition' => 'MISB',
                'weight' => 90,
                'length' => 9,
                'width' => 7,
                'height' => 1,
                'image_type' => 'merch',
                'description' => "OVERVIEW\n\t• Japanese premium 64 x 89mm tight-fit inner sleeves essential for double-sleeving high-value trading cards.",
            ],
            [
                'name' => 'Ultra PRO 35pt UV One-Touch Magnetic Card Holder [Pack of 5]',
                'sku' => 'ACC-UP-ONETOUCH-35PT-5PK',
                'price' => 850.00,
                'brand' => 'The Pokémon Company',
                'category' => 'Anime Merch Collectibles',
                'subcategory' => 'Stationery & Clear Files',
                'condition' => 'MISB',
                'weight' => 280,
                'length' => 15,
                'width' => 10,
                'height' => 6,
                'image_type' => 'merch',
                'description' => "OVERVIEW\n\t• Laboratory-grade UV-blocking magnetic display cases with diamond corners to protect card corners from pressure damage.",
            ],
            [
                'name' => 'Vault X 9-Pocket eXo-Tec Zip-Up Trading Card Binder (360 Cards)',
                'sku' => 'ACC-VX-ZIPBINDER-9P',
                'price' => 1650.00,
                'brand' => 'The Pokémon Company',
                'category' => 'Anime Merch Collectibles',
                'subcategory' => 'Stationery & Clear Files',
                'condition' => 'MISB',
                'weight' => 780,
                'length' => 35,
                'width' => 27,
                'height' => 4,
                'image_type' => 'merch',
                'description' => "OVERVIEW\n\t• Heavy duty water-resistant eXo-Tec padded zip binder with side-loading pockets to prevent cards from slipping out during transport.",
            ],
            [
                'name' => 'Official Pokémon Center: Eevee Sleepy Soft Plush Doll (30cm)',
                'sku' => 'MRC-PKM-PLUSH-EEVEE-30',
                'price' => 1850.00,
                'brand' => 'The Pokémon Company',
                'category' => 'Anime Merch Collectibles',
                'subcategory' => 'Plushies & Nesoberi',
                'condition' => 'Brandnew',
                'weight' => 420,
                'length' => 30,
                'width' => 22,
                'height' => 18,
                'image_type' => 'merch',
                'description' => "OVERVIEW\n\t• Official Pokémon Center Japan licensed super-soft plushie of Eevee resting in sleeping pose with embroidered facial details.",
            ],
            [
                'name' => 'Official Pokémon Center: Gengar Big Mouth Plush (35cm)',
                'sku' => 'MRC-PKM-PLUSH-GENGAR-35',
                'price' => 2200.00,
                'brand' => 'The Pokémon Company',
                'category' => 'Anime Merch Collectibles',
                'subcategory' => 'Plushies & Nesoberi',
                'condition' => 'Brandnew',
                'weight' => 580,
                'length' => 35,
                'width' => 30,
                'height' => 25,
                'image_type' => 'merch',
                'description' => "OVERVIEW\n\t• Large cuddly Gengar plush with felt teeth, mischievous grin, and premium micro-velour fur.",
            ],
        ];
    }
}
