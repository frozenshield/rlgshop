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
        $data = [
            'TCG (Trading Cards)' => [
                'Pokemon',
                'Yugioh',
                'Duel Master',
                'One Piece',
                'Gundam',
                'Hololive',
                'Dragon Ball',
                'Weiss Schwarz',
                'Battle Spirit',
            ],
            'Gunpla' => [
                'High Grade (HG)',
                'Real Grade (RG)',
                'Master Grade (MG)',
                'Perfect Grade (PG)',
                'Super Deformed (SD)',
                'Entry Grade (EG)',
                'Option Parts & Decals',
            ],
            'Anime Figures' => [
                'Scale Figures',
                'Nendoroids',
                'Pop Up Parade',
                'Action Figures / Figma',
                'Prize Figures',
                'Statues & Busts',
            ],
            'Anime Merch Collectibles' => [
                'Plushies & Nesoberi',
                'Acrylic Stands',
                'Keychains & Straps',
                'Badges & Pins',
                'Apparel & T-Shirts',
                'Posters & Wall Scrolls',
                'Stationery & Clear Files',
            ],
        ];

        foreach ($data as $catName => $subcategories) {
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
