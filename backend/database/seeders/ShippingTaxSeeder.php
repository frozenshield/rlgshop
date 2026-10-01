<?php

namespace Database\Seeders;

use App\Models\ShippingTax;
use Illuminate\Database\Seeder;

class ShippingTaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShippingTax::updateOrCreate(
            ['id' => 1],
            [
                'standard_shipping_fee' => 100.00,
                'free_shipping_threshold' => 2500.00,
                'vat_percentage' => 12.00,
                'is_active' => true,
            ]
        );
    }
}
