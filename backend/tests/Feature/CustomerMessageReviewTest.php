<?php

namespace Tests\Feature;

use App\Models\CustomerMessage;
use App\Models\CustomerProfile;
use App\Models\CustomerReview;
use App\Models\Product;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\CustomerMessageReviewSeeder;
use Database\Seeders\CustomerProfileSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RefBrandSeeder;
use Database\Seeders\RefCategorySeeder;
use Database\Seeders\RefConditionSeeder;
use Database\Seeders\RefStaffRoleSeeder;
use Database\Seeders\StaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerMessageReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RefCategorySeeder::class);
        $this->seed(RefBrandSeeder::class);
        $this->seed(RefConditionSeeder::class);
        $this->seed(RefStaffRoleSeeder::class);
        $this->seed(StaffSeeder::class);
        $this->seed(CustomerProfileSeeder::class);
        $this->seed(ProductSeeder::class);
        $this->seed(CustomerMessageReviewSeeder::class);
    }

    public function test_customer_message_table_has_required_schema(): void
    {
        $this->assertTrue(Schema::hasTable('customer_message'));
        $this->assertTrue(Schema::hasColumns('customer_message', [
            'id',
            'user_id',
            'message',
            'status',
        ]));
    }

    public function test_customer_review_table_has_required_schema(): void
    {
        $this->assertTrue(Schema::hasTable('customer_review'));
        $this->assertTrue(Schema::hasColumns('customer_review', [
            'id',
            'user_id',
            'stars',
            'message',
            'image',
            'staff_reply',
        ]));
    }

    public function test_customer_profiles_table_has_segment_rank_column(): void
    {
        $this->assertTrue(Schema::hasColumn('customer_profiles', 'segment_rank'));
    }

    public function test_can_list_and_create_customer_messages(): void
    {
        $user = User::first();

        // 1. Unauthenticated request never leaks messages
        $unauthResponse = $this->getJson('/api/customer-messages');
        $unauthResponse->assertStatus(200);
        $this->assertEmpty($unauthResponse->json('data'));

        // Authenticate as user
        $this->actingAs($user);

        // 2. Create message
        $storeResponse = $this->postJson('/api/customer-messages', [
            'user_id' => $user->id,
            'subject' => 'Restock Question',
            'message' => 'When will Pok�mon 151 booster boxes be restocked?',
            'status' => 'ongoing',
        ]);

        $storeResponse->assertStatus(201);
        $storeResponse->assertJsonPath('data.status', 'ongoing');
        $storeResponse->assertJsonPath('data.message', 'When will Pok�mon 151 booster boxes be restocked?');

        $this->assertDatabaseHas('customer_message', [
            'user_id' => $user->id,
            'subject' => 'Restock Question',
            'status' => 'ongoing',
        ]);

        // 3. User lists their scoped messages
        $listResponse = $this->getJson('/api/customer-messages');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonPath('success', true);
        $this->assertNotEmpty($listResponse->json('data'));
        $this->assertEquals($user->id, $listResponse->json('data.0.user_id'));
    }

    public function test_staff_can_reply_to_customer_message_and_resolve(): void
    {
        $message = CustomerMessage::where('status', 'ongoing')->first();
        $this->assertNotNull($message);
        $staff = Staff::first();

        $response = $this->postJson("/api/customer-messages/{$message->id}/reply", [
            'staff_reply' => 'Hi, stock will arrive this Friday at 5:00 PM!',
            'staff_id' => $staff->id,
            'status' => 'resolve',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'resolve');
        $response->assertJsonPath('data.staff_reply', 'Hi, stock will arrive this Friday at 5:00 PM!');

        $message->refresh();
        $this->assertEquals('resolve', $message->status);
        $this->assertEquals('Hi, stock will arrive this Friday at 5:00 PM!', $message->staff_reply);
        $this->assertNotNull($message->resolved_at);
    }

    public function test_can_update_customer_message_status(): void
    {
        $message = CustomerMessage::first();

        $response = $this->patchJson("/api/customer-messages/{$message->id}/status", [
            'status' => 'resolve',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('resolve', $message->fresh()->status);

        $reopenResponse = $this->patchJson("/api/customer-messages/{$message->id}/status", [
            'status' => 'ongoing',
        ]);

        $reopenResponse->assertStatus(200);
        $this->assertEquals('ongoing', $message->fresh()->status);
    }

    public function test_can_list_and_create_customer_review(): void
    {
        $user = User::first();
        $product = Product::first();

        // 1. Fetch reviews
        $listResponse = $this->getJson('/api/customer-reviews');
        $listResponse->assertStatus(200);
        $this->assertNotEmpty($listResponse->json('data'));

        // 2. Submit new review
        $createResponse = $this->postJson('/api/customer-reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'stars' => 5,
            'message' => 'Best Japanese card box ever opened! Hit the SAR Charizard!',
            'image' => 'https://example.com/charizard-pull.jpg',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonPath('data.stars', 5);
        $createResponse->assertJsonPath('data.message', 'Best Japanese card box ever opened! Hit the SAR Charizard!');

        $this->assertDatabaseHas('customer_review', [
            'user_id' => $user->id,
            'stars' => 5,
            'image' => 'https://example.com/charizard-pull.jpg',
        ]);
    }

    public function test_customer_review_validates_star_rating_range(): void
    {
        $user = User::first();

        $response = $this->postJson('/api/customer-reviews', [
            'user_id' => $user->id,
            'stars' => 6, // Invalid > 5
            'message' => 'Too high',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['stars']);
    }

    public function test_staff_can_reply_to_customer_review_via_api(): void
    {
        $review = CustomerReview::first();
        $this->assertNotNull($review);
        $staff = Staff::first();

        $response = $this->postJson("/api/customer-reviews/{$review->id}/reply", [
            'staff_reply' => 'Congratulations on the insane pull! Thank you for ordering from RLG.',
            'staff_id' => $staff->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.staff_reply', 'Congratulations on the insane pull! Thank you for ordering from RLG.');

        $review->refresh();
        $this->assertEquals('Congratulations on the insane pull! Thank you for ordering from RLG.', $review->staff_reply);
        $this->assertNotNull($review->replied_at);
    }

    public function test_staff_can_update_customer_segment_rank_via_crm_card_action(): void
    {
        $profile = CustomerProfile::first();
        $this->assertNotNull($profile);

        // Update to VIP
        $response = $this->patchJson("/api/customer-profiles/{$profile->id}/segment-rank", [
            'segment_rank' => 'VIP',
            'notes' => 'High lifetime value, designated as VIP Collector by Staff.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.segment_rank', 'VIP');

        $profile->refresh();
        $this->assertEquals('VIP', $profile->segment_rank);
        $this->assertEquals('VIP', $profile->segment);
        $this->assertEquals('High lifetime value, designated as VIP Collector by Staff.', $profile->notes);

        // Update to Wholesale
        $wholesaleResponse = $this->patchJson("/api/customer-profiles/{$profile->id}/segment-rank", [
            'segment_rank' => 'Wholesale',
        ]);
        $wholesaleResponse->assertStatus(200);
        $wholesaleResponse->assertJsonPath('data.segment_rank', 'Wholesale');
        $this->assertEquals('Wholesale', $profile->fresh()->segment_rank);
    }

    public function test_segment_rank_validation_rejects_invalid_ranks(): void
    {
        $profile = CustomerProfile::first();

        $response = $this->patchJson("/api/customer-profiles/{$profile->id}/segment-rank", [
            'segment_rank' => 'SuperMegaCollector', // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['segment_rank']);
    }
}
