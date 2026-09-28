<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminStaff = Staff::first();
        $adminUser = User::firstOrCreate(
            ['email' => $adminStaff?->email ?? 'admin@rlghobby.com'],
            [
                'name' => $adminStaff?->name ?? 'Admin Chief',
                'password' => 'AdminPass2026!',
                'user_type' => 'admin',
            ]
        );

        $marcus = User::where('email', 'marcus.tan@example.com')->first();
        $elena = User::where('email', 'elena.reyes@example.com')->first();
        $david = User::where('email', 'david.cruz@example.com')->first();

        // 1. Conversation with Marcus Tan (Active case break inquiry)
        if ($marcus) {
            $convMarcus = Conversation::updateOrCreate(
                ['customer_id' => $marcus->id],
                [
                    'admin_id' => $adminUser->id,
                    'status' => 'active',
                ]
            );

            // Re-seed conversation messages
            $convMarcus->messages()->delete();

            Message::create([
                'conversation_id' => $convMarcus->id,
                'sender_id' => $marcus->id,
                'content' => 'Hi team! Will you be taking pre-orders for One Piece OP-09 cases? How many booster boxes per customer is the limit?',
                'is_read' => true,
                'created_at' => now()->subHours(5),
            ]);

            Message::create([
                'conversation_id' => $convMarcus->id,
                'sender_id' => $adminUser->id,
                'content' => 'Hello Marcus! Yes, we have allocated 2 sealed master cases for pre-orders. Maximum 2 booster boxes per verified collector profile to ensure everyone gets an allocation.',
                'is_read' => true,
                'created_at' => now()->subHours(4)->addMinutes(15),
            ]);

            Message::create([
                'conversation_id' => $convMarcus->id,
                'sender_id' => $marcus->id,
                'content' => 'Awesome! Can I pay via GCash right now to lock in my 2 boxes?',
                'is_read' => true,
                'created_at' => now()->subHours(3),
            ]);

            Message::create([
                'conversation_id' => $convMarcus->id,
                'sender_id' => $adminUser->id,
                'content' => 'Yes, GCash and Maya are supported! Simply select GCash at checkout and upload your reference receipt. We will pack it with corner armor protectors.',
                'is_read' => true,
                'created_at' => now()->subHours(2)->addMinutes(10),
            ]);

            Message::create([
                'conversation_id' => $convMarcus->id,
                'sender_id' => $marcus->id,
                'content' => 'Order placed! Thank you so much for the quick confirmation!',
                'is_read' => false,
                'created_at' => now()->subMinutes(25),
            ]);

            $convMarcus->touch();
        }

        // 2. Conversation with Elena Reyes (Resolved tracking inquiry)
        if ($elena) {
            $convElena = Conversation::updateOrCreate(
                ['customer_id' => $elena->id],
                [
                    'admin_id' => $adminUser->id,
                    'status' => 'resolved',
                ]
            );

            $convElena->messages()->delete();

            Message::create([
                'conversation_id' => $convElena->id,
                'sender_id' => $elena->id,
                'content' => 'Good day! My J&T tracking number PH-JT-894729104 showed pending pickup for 24 hours. Has the courier collected it from your hub?',
                'is_read' => true,
                'created_at' => now()->subDays(2),
            ]);

            Message::create([
                'conversation_id' => $convElena->id,
                'sender_id' => $adminUser->id,
                'content' => 'Hi Elena! Our fulfillment team Darwin just handed it off to J&T Express truck at 4:30 PM. The tracking portal should update within the next 2 hours.',
                'is_read' => true,
                'created_at' => now()->subDays(2)->addHours(2),
            ]);

            Message::create([
                'conversation_id' => $convElena->id,
                'sender_id' => $elena->id,
                'content' => 'Tracking just refreshed and it is already in transit! Thank you for the update!',
                'is_read' => true,
                'created_at' => now()->subDay(),
            ]);

            $convElena->touch();
        }

        // 3. Conversation with David Cruz (Restock inquiry)
        if ($david) {
            $convDavid = Conversation::updateOrCreate(
                ['customer_id' => $david->id],
                [
                    'admin_id' => $adminUser->id,
                    'status' => 'active',
                ]
            );

            $convDavid->messages()->delete();

            Message::create([
                'conversation_id' => $convDavid->id,
                'sender_id' => $david->id,
                'content' => 'Good evening! When will Japanese Pokémon Card 151 (SV2a) booster boxes be back in stock?',
                'is_read' => true,
                'created_at' => now()->subHours(12),
            ]);

            Message::create([
                'conversation_id' => $convDavid->id,
                'sender_id' => $adminUser->id,
                'content' => 'Hi David! We have 20 fresh factory-sealed boxes arriving directly from Japan this Friday. They will go live at 6:00 PM PST.',
                'is_read' => true,
                'created_at' => now()->subHours(10),
            ]);

            Message::create([
                'conversation_id' => $convDavid->id,
                'sender_id' => $david->id,
                'content' => 'Will there be a promo discount code for returning collectors?',
                'is_read' => false,
                'created_at' => now()->subMinutes(45),
            ]);

            $convDavid->touch();
        }
    }
}
