<?php

namespace Database\Seeders;

use App\Models\RefBrand;
use Illuminate\Database\Seeder;

class RefBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'The Pokémon Company',
            'Bandai',
            'Bandai Spirits',
            'Banpresto',
            'Good Smile Company',
            'Kotobukiya',
            'Bushiroad',
            'Takara Tomy',
            'Konami',
            'MegaHouse',
            'Max Factory',
            'Aniplex',
            'FuRyu',
            'SEGA',
            'Taito',
            'Kadokawa',
            'Alter',
            'Square Enix',
            'Broccoli',
            'Ensky',
            'Media Factory',
            'Sanrio',
        ];

        foreach ($brands as $brand) {
            RefBrand::firstOrCreate(['name' => $brand]);
        }
    }
}
