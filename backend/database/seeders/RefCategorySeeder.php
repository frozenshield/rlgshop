<?php

namespace Database\Seeders;

use App\Models\RefCategory;
use App\Models\RefSubcategory;
use Illuminate\Database\Seeder;

class RefCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'TCG (Trading Cards)' => [
                'Pokémon TCG',
                'One Piece Card Game',
                'Hololive Official Card Game',
                'Duel Masters TCG',
                'Weiß Schwarz',
                'Yu-Gi-Oh!',
            ],
            'Anime Figures' => [
                'Scale Figures',
                'Nendoroids',
                'Pop Up Parade',
                'Action Figures',
                'Prize Figures',
            ],
            'Gunpla & Model Kits' => [
                'High Grade (HG)',
                'Real Grade (RG)',
                'Master Grade (MG)',
                'Perfect Grade (PG)',
            ],
            'Anime Merchandise' => [
                'Plushies',
                'Apparel',
                'Keychains & Pins',
                'Collector Accessories',
            ],
            'Hobby Supplies' => [
                'Card Sleeves & Binders',
                'Deck Boxes',
                'Toploaders & Acrylic Cases',
                'Display Stands',
            ],
        ];

        foreach ($categories as $catName => $subcategories) {
            $cat = RefCategory::firstOrCreate(['desc' => $catName]);

            foreach ($subcategories as $subName) {
                RefSubcategory::firstOrCreate([
                    'ref_category_id' => $cat->id,
                    'desc' => $subName,
                ]);
            }
        }
    }
}
