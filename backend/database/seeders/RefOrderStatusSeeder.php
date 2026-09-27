<?php

namespace Database\Seeders;

use App\Models\RefOrderStatus;
use Illuminate\Database\Seeder;

class RefOrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'pending',
                'label' => 'Pending',
                'badge_color' => 'amber',
                'description' => 'Order submitted and awaiting payment confirmation or processing.',
            ],
            [
                'name' => 'processing',
                'label' => 'Processing',
                'badge_color' => 'blue',
                'description' => 'Payment confirmed, order items being picked and packed.',
            ],
            [
                'name' => 'shipped',
                'label' => 'Shipped',
                'badge_color' => 'purple',
                'description' => 'Dispatched with tracking number via shipping carrier.',
            ],
            [
                'name' => 'delivered',
                'label' => 'Delivered',
                'badge_color' => 'emerald',
                'description' => 'Successfully delivered to customer shipping address.',
            ],
            [
                'name' => 'accepted',
                'label' => 'Accepted',
                'badge_color' => 'indigo',
                'description' => 'Order accepted by store or confirmed by customer.',
            ],
            [
                'name' => 'cancel',
                'label' => 'Canceled',
                'badge_color' => 'rose',
                'description' => 'Order canceled by customer or store administrator.',
            ],
            [
                'name' => 'refund',
                'label' => 'Refunded',
                'badge_color' => 'pink',
                'description' => 'Order payment has been refunded to customer.',
            ],
            [
                'name' => 'return',
                'label' => 'Returned',
                'badge_color' => 'orange',
                'description' => 'Parcel returned to sender or merchant.',
            ],
        ];

        foreach ($statuses as $status) {
            RefOrderStatus::updateOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}
