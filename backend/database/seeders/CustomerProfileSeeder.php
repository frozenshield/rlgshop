<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed or find the primary customer account from Image 1
        $user1 = User::firstOrCreate(
            ['email' => 'russelluisg@gmail.com'],
            [
                'name' => 'Russel Luis Gementiza',
                'password' => Hash::make('password123'),
                'user_type' => 'customer',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
            ]
        );

        CustomerProfile::updateOrCreate(
            ['user_id' => $user1->id],
            [
                'name' => 'Russel Luis Gementiza',
                'username' => 'hobby_collector',
                'phone' => '+63 912 345 6789',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&auto=format&fit=crop&q=80',
                'address_line1' => '123 Hobby St, Metro Manila',
                'city' => 'Quezon City',
                'postal_code' => '1100',
                'country' => 'Philippines',
                'favorite_franchise' => 'Pokémon TCG',
                'bio' => 'Sealed box collector and Gunpla builder since 2018.',
                'two_factor_auth' => false,
                'email_notifications' => true,
                'order_updates_sms' => true,
                'marketing_emails' => false,
                'currency_preference' => 'PHP',
                'public_collection' => true,
                'segment' => 'VIP',
                'notes' => 'Early beta tester, high-value Pokémon booster box enthusiast.',
            ]
        );

        // 2. Also ensure other registered users have a default customer profile
        $users = User::whereDoesntHave('customerProfile')->get();
        foreach ($users as $user) {
            CustomerProfile::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $user->name ?? 'collector')),
                'phone' => null,
                'avatar' => $user->avatar,
                'address_line1' => null,
                'city' => 'Metro Manila',
                'postal_code' => '1000',
                'country' => 'Philippines',
                'favorite_franchise' => 'Pokémon TCG',
                'bio' => null,
                'two_factor_auth' => false,
                'email_notifications' => true,
                'order_updates_sms' => true,
                'marketing_emails' => false,
                'currency_preference' => 'PHP',
                'public_collection' => true,
                'segment' => 'Regular',
            ]);
        }
    }
}
