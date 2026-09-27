<?php

namespace Database\Seeders;

use App\Models\RefShippingCarrier;
use Illuminate\Database\Seeder;

class RefShippingCarrierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carriers = [
            [
                'name' => 'J&T Express Philippines',
                'code' => 'jnt_ph',
                'short_name' => 'J&T Express',
                'tracking_url_template' => 'https://www.jtexpress.ph/index/query/gzquery.html?bills={tracking}',
                'is_active' => true,
            ],
            [
                'name' => 'LBC Express (Door to Door)',
                'code' => 'lbc',
                'short_name' => 'LBC Express',
                'tracking_url_template' => 'https://www.lbcexpress.com/track/?tracking_no={tracking}',
                'is_active' => true,
            ],
            [
                'name' => 'Ninja Van Standard',
                'code' => 'ninjavan',
                'short_name' => 'Ninja Van',
                'tracking_url_template' => 'https://www.ninjavan.co/en-ph/tracking?id={tracking}',
                'is_active' => true,
            ],
            [
                'name' => 'Flash Express Priority',
                'code' => 'flash',
                'short_name' => 'Flash Express',
                'tracking_url_template' => 'https://www.flashexpress.ph/tracking/?se={tracking}',
                'is_active' => true,
            ],
            [
                'name' => 'DHL Express International',
                'code' => 'dhl',
                'short_name' => 'DHL Express',
                'tracking_url_template' => 'https://www.dhl.com/ph-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}',
                'is_active' => true,
            ],
        ];

        foreach ($carriers as $carrier) {
            RefShippingCarrier::updateOrCreate(
                ['code' => $carrier['code']],
                $carrier
            );
        }
    }
}
