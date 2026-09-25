<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promos = [
            [
                'code' => 'HOBBY10',
                'type' => 'percentage',
                'value' => 10.00,
                'usage_count' => 142,
                'usage_limit' => 500,
                'expiry_date' => '2026-12-31',
                'is_active' => true,
                'min_order_amount' => 500.00,
                'description' => 'Collector welcome discount: 10% OFF all hobby merchandise.',
            ],
            [
                'code' => 'GUNPLA20',
                'type' => 'percentage',
                'value' => 20.00,
                'usage_count' => 88,
                'usage_limit' => 200,
                'expiry_date' => '2026-10-31',
                'is_active' => true,
                'min_order_amount' => 1000.00,
                'description' => 'Gunpla builders flash markdown: 20% OFF model kits and tools.',
            ],
            [
                'code' => 'FREESHIPPH',
                'type' => 'shipping',
                'value' => 0.00,
                'usage_count' => 65,
                'usage_limit' => 300,
                'expiry_date' => '2026-11-15',
                'is_active' => true,
                'min_order_amount' => 1500.00,
                'description' => 'Free nationwide shipping on orders above ₱1,500.',
            ],
        ];

        foreach ($promos as $promo) {
            PromoCode::updateOrCreate(
                ['code' => $promo['code']],
                $promo
            );
        }
    }
}
