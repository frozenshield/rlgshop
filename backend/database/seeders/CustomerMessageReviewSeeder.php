<?php

namespace Database\Seeders;

use App\Models\CustomerMessage;
use App\Models\CustomerReview;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerMessageReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marcus = User::where('email', 'marcus.tan@example.com')->first() ?? User::first();
        $elena = User::where('email', 'elena.reyes@example.com')->first() ?? User::first();
        $david = User::where('email', 'david.cruz@example.com')->first() ?? User::first();
        $staff = Staff::first();
        $product = Product::first();

        if ($marcus) {
            CustomerMessage::updateOrCreate(
                [
                    'user_id' => $marcus->id,
                    'subject' => 'Pre-order allocation for One Piece OP-09 The Four Emperors?',
                ],
                [
                    'message' => 'Hi team, will you be taking pre-orders for OP-09 cases? How many booster boxes per customer is the limit?',
                    'status' => 'ongoing',
                ]
            );
        }

        if ($elena) {
            CustomerMessage::updateOrCreate(
                [
                    'user_id' => $elena->id,
                    'subject' => 'Courier delay on parcel tracking #PH-JT-894729104',
                ],
                [
                    'message' => 'My package tracking shows pending pickup at warehouse for 48 hours. Can you expedite with J&T?',
                    'status' => 'ongoing',
                ]
            );
        }

        if ($david) {
            CustomerMessage::updateOrCreate(
                [
                    'user_id' => $david->id,
                    'subject' => 'Pokemon 151 Japanese Booster Box Restock Inquiry',
                ],
                [
                    'message' => 'Will you have more sealed boxes of Pokemon Card 151 (SV2a) arriving this week?',
                    'status' => 'resolve',
                    'staff_reply' => 'Hi David, yes! A new shipment is arriving this Friday and will go live at 6:00 PM.',
                    'staff_id' => $staff?->id,
                    'resolved_at' => now()->subDay(),
                ]
            );
        }

        if ($marcus) {
            CustomerReview::updateOrCreate(
                [
                    'user_id' => $marcus->id,
                    'message' => 'Ordered the Pokemon 151 booster box. Arrived sealed in pristine bubble wrap, mint condition!',
                ],
                [
                    'product_id' => $product?->id,
                    'stars' => 5,
                    'image' => 'https://images.unsplash.com/photo-1613771404784-3a5686aa2be3?w=600&auto=format&fit=crop&q=80',
                    'staff_reply' => 'Thank you Marcus! We take extra care in packaging all collectible booster boxes.',
                    'staff_id' => $staff?->id,
                    'replied_at' => now()->subHours(5),
                ]
            );
        }

        if ($elena) {
            CustomerReview::updateOrCreate(
                [
                    'user_id' => $elena->id,
                    'message' => 'Fast delivery with J&T Express and very accommodating customer support team. Cards are 100% authentic!',
                ],
                [
                    'product_id' => $product?->id,
                    'stars' => 5,
                    'image' => null,
                    'staff_reply' => 'Much appreciated Elena! Enjoy your collection.',
                    'staff_id' => $staff?->id,
                    'replied_at' => now()->subHours(2),
                ]
            );
        }

        if ($david) {
            CustomerReview::updateOrCreate(
                [
                    'user_id' => $david->id,
                    'message' => 'Great booster box selection. Looking forward to more Japanese expansions and singles restocks.',
                ],
                [
                    'product_id' => $product?->id,
                    'stars' => 4,
                    'image' => null,
                    'staff_reply' => null,
                    'staff_id' => null,
                ]
            );
        }
    }
}
