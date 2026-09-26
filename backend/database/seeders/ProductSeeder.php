<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RefBrand;
use App\Models\RefCategory;
use App\Models\RefCondition;
use App\Models\RefSubcategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pokemonBrand = RefBrand::firstOrCreate(['name' => 'The Pokémon Company']);
        $bandaiBrand = RefBrand::firstOrCreate(['name' => 'Bandai']);
        $bandaiSpirits = RefBrand::firstOrCreate(['name' => 'Bandai Spirits']);
        $banpresto = RefBrand::firstOrCreate(['name' => 'Banpresto']);
        $gsc = RefBrand::firstOrCreate(['name' => 'Good Smile Company']);
        $bushiroad = RefBrand::firstOrCreate(['name' => 'Bushiroad']);

        $tcgCat = RefCategory::firstOrCreate(['desc' => 'TCG (Trading Cards)']);
        $gunplaCat = RefCategory::firstOrCreate(['desc' => 'Gunpla']);
        $figureCat = RefCategory::firstOrCreate(['desc' => 'Anime Figures']);

        $pokemonSub = RefSubcategory::firstOrCreate(['ref_category_id' => $tcgCat->id, 'desc' => 'Pokemon']);
        $opSub = RefSubcategory::firstOrCreate(['ref_category_id' => $tcgCat->id, 'desc' => 'One Piece']);
        $holoSub = RefSubcategory::firstOrCreate(['ref_category_id' => $tcgCat->id, 'desc' => 'Hololive']);

        $rgSub = RefSubcategory::firstOrCreate(['ref_category_id' => $gunplaCat->id, 'desc' => 'Real Grade (RG)']);
        $mgSub = RefSubcategory::firstOrCreate(['ref_category_id' => $gunplaCat->id, 'desc' => 'Master Grade (MG)']);

        $scaleSub = RefSubcategory::firstOrCreate(['ref_category_id' => $figureCat->id, 'desc' => 'Scale Figures']);
        $nendoSub = RefSubcategory::firstOrCreate(['ref_category_id' => $figureCat->id, 'desc' => 'Nendoroids']);

        $nearMint = RefCondition::firstOrCreate(['desc' => 'Near Mint']);
        $misb = RefCondition::firstOrCreate(['desc' => 'MISB']);

        $products = [
            [
                'name' => 'Pokémon TCG: Scarlet & Violet 151 Elite Trainer Box',
                'sku' => 'TCG-PKM-151-ETB',
                'stock' => 25,
                'description' => 'Relive the original Kanto journey with 9 booster packs of Scarlet & Violet 151, an exclusive Snorlax illustration rare promo card, 65 premium card sleeves, 45 Energy cards, and a collector deck box!',
                'ref_brand_id' => $pokemonBrand->id,
                'ref_category_id' => $tcgCat->id,
                'ref_subcategory_id' => $pokemonSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 2799.00,
                'weight' => 650.00,
                'length' => 19.00,
                'width' => 16.50,
                'height' => 8.50,
                'status' => 'active',
                'rating' => 4.90,
                'review_count' => 38,
                'image_url' => 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Pokémon TCG: Charizard ex Super-Premium Collection Box',
                'sku' => 'TCG-PKM-CHZ-EX',
                'stock' => 15,
                'description' => 'Ignite your collection with the ultimate Charizard ex box! Includes 3 etched foil promo cards (Charmander, Charmeleon, and Charizard ex), 10 Pokémon TCG booster packs, and an illuminated card display figure stand.',
                'ref_brand_id' => $pokemonBrand->id,
                'ref_category_id' => $tcgCat->id,
                'ref_subcategory_id' => $pokemonSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 4499.00,
                'weight' => 900.00,
                'length' => 30.00,
                'width' => 23.00,
                'height' => 10.00,
                'status' => 'active',
                'rating' => 5.00,
                'review_count' => 19,
                'image_url' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'One Piece Card Game: OP-05 Awakening of the New Era Booster Box',
                'sku' => 'TCG-OP-05-BOX',
                'stock' => 8,
                'description' => 'Official Bandai sealed Japanese/English 24-pack booster box celebrating the 1st anniversary with Gear 5 Luffy, Manga Sabo, and revolutionary army leaders!',
                'ref_brand_id' => $bandaiBrand->id,
                'ref_category_id' => $tcgCat->id,
                'ref_subcategory_id' => $opSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 4750.00,
                'weight' => 420.00,
                'length' => 14.50,
                'width' => 14.00,
                'height' => 4.00,
                'status' => 'active',
                'rating' => 4.95,
                'review_count' => 64,
                'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Hololive Official Card Game: Blooming Radiance Booster Box',
                'sku' => 'TCG-HOLO-BP01',
                'stock' => 12,
                'description' => 'Bushiroad sealed 16-pack booster box containing Hololive VTuber idol talent cards, sign cards (SP), and debut illustration foils.',
                'ref_brand_id' => $bushiroad->id,
                'ref_category_id' => $tcgCat->id,
                'ref_subcategory_id' => $holoSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 3950.00,
                'weight' => 380.00,
                'length' => 14.00,
                'width' => 14.00,
                'height' => 3.80,
                'status' => 'active',
                'rating' => 4.85,
                'review_count' => 28,
                'image_url' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'RG 1/144 RX-78-2 Gundam Ver.2.0 Model Kit',
                'sku' => 'GUN-RG-RX78-2',
                'stock' => 18,
                'description' => 'Bandai Spirits next-generation Real Grade kit with fully articulated MS Advanced Joint inner frame, realistic metallic decals, beam rifle, core fighter, and hyper bazooka.',
                'ref_brand_id' => $bandaiSpirits->id,
                'ref_category_id' => $gunplaCat->id,
                'ref_subcategory_id' => $rgSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 2550.00,
                'weight' => 520.00,
                'length' => 30.00,
                'width' => 19.00,
                'height' => 7.50,
                'status' => 'active',
                'rating' => 4.90,
                'review_count' => 42,
                'image_url' => 'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'MG 1/100 Freedom Gundam Ver.2.0 Model Kit',
                'sku' => 'GUN-MG-FREEDOM',
                'stock' => 4,
                'description' => 'Master Grade 1/100 scale kit featuring wing binders with telescoping mechanisms, beam sabers, shield, and dedicated display stand from Mobile Suit Gundam SEED.',
                'ref_brand_id' => $bandaiSpirits->id,
                'ref_category_id' => $gunplaCat->id,
                'ref_subcategory_id' => $mgSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 3200.00,
                'weight' => 880.00,
                'length' => 39.00,
                'width' => 31.00,
                'height' => 9.50,
                'status' => 'active',
                'rating' => 4.92,
                'review_count' => 31,
                'image_url' => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Monkey D. Luffy Gear 5 Sun God Nika Battle Figure',
                'sku' => 'FIG-OP-LUFFY-G5',
                'stock' => 10,
                'description' => 'Dynamic battle stance scale figure of Monkey D. Luffy transformed into Sun God Nika with lightning base effects and detailed pearlescent white smoke sculpt.',
                'ref_brand_id' => $banpresto->id,
                'ref_category_id' => $figureCat->id,
                'ref_subcategory_id' => $scaleSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 2688.00,
                'weight' => 750.00,
                'length' => 22.00,
                'width' => 18.00,
                'height' => 26.00,
                'status' => 'active',
                'rating' => 4.95,
                'review_count' => 53,
                'image_url' => 'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => "Nendoroid Frieren (Beyond Journey's End)",
                'sku' => 'FIG-NEN-FRIEREN',
                'stock' => 6,
                'description' => 'Good Smile Company authentic Nendoroid of the mage Frieren with interchangeable neutral/smug faces, staff, grimoire, blue-moon weed field, and potion bottle.',
                'ref_brand_id' => $gsc->id,
                'ref_category_id' => $figureCat->id,
                'ref_subcategory_id' => $nendoSub->id,
                'ref_condition_id' => $misb->id,
                'price' => 2850.00,
                'weight' => 290.00,
                'length' => 17.50,
                'width' => 13.50,
                'height' => 9.00,
                'status' => 'active',
                'rating' => 4.88,
                'review_count' => 26,
                'image_url' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Pokémon TCG Leafeon GX 012/066 RR Ultra Sun',
                'sku' => 'TCG-PKM-LEA-GX',
                'stock' => 5,
                'description' => 'Authentic Japanese Leafeon GX Ultra Sun single card in Near Mint condition, protected with penny sleeve and top-loader.',
                'ref_brand_id' => $pokemonBrand->id,
                'ref_category_id' => $tcgCat->id,
                'ref_subcategory_id' => $pokemonSub->id,
                'ref_condition_id' => $nearMint->id,
                'price' => 450.00,
                'weight' => 15.00,
                'length' => 9.00,
                'width' => 7.00,
                'height' => 0.50,
                'status' => 'active',
                'rating' => 4.80,
                'review_count' => 14,
                'image_url' => 'https://images.unsplash.com/photo-1628155930542-3c7a64e2c833?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Pokémon TCG Japanese Shiny Star V Abomasnow 200/190 S Shiny Rare',
                'sku' => 'TCG-PKM-ABO-S',
                'stock' => 6,
                'description' => 'Near Mint Japanese Shiny Star V (s4a) shiny rare holographic Abomasnow single card, collector sleeved.',
                'ref_brand_id' => $pokemonBrand->id,
                'ref_category_id' => $tcgCat->id,
                'ref_subcategory_id' => $pokemonSub->id,
                'ref_condition_id' => $nearMint->id,
                'price' => 250.00,
                'weight' => 15.00,
                'length' => 9.00,
                'width' => 7.00,
                'height' => 0.50,
                'status' => 'active',
                'rating' => 4.70,
                'review_count' => 8,
                'image_url' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80',
            ],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['sku' => $p['sku']],
                $p
            );
        }
    }
}
