<?php

namespace Tests\Feature;

use App\Models\CustomerMessage;
use App\Models\CustomerOrder;
use App\Models\RefOrderStatus;
use App\Models\User;
use Database\Seeders\CustomerMessageReviewSeeder;
use Database\Seeders\CustomerOrderSeeder;
use Database\Seeders\CustomerProfileSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RefBrandSeeder;
use Database\Seeders\RefCategorySeeder;
use Database\Seeders\RefConditionSeeder;
use Database\Seeders\RefOrderStatusSeeder;
use Database\Seeders\RefPaymentMethodSeeder;
use Database\Seeders\RefShippingCarrierSeeder;
use Database\Seeders\RefStaffRoleSeeder;
use Database\Seeders\StaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScopedOrderAndMessageSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected User $staffUser;

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
        $this->seed(RefOrderStatusSeeder::class);
        $this->seed(RefShippingCarrierSeeder::class);
        $this->seed(RefPaymentMethodSeeder::class);
        $this->seed(CustomerOrderSeeder::class);
        $this->seed(CustomerMessageReviewSeeder::class);

        $this->userA = User::firstOrCreate(
            ['email' => 'user_a@example.com'],
            ['name' => 'User Alpha', 'user_type' => 'customer']
        );

        $this->userB = User::firstOrCreate(
            ['email' => 'user_b@example.com'],
            ['name' => 'User Bravo', 'user_type' => 'customer']
        );

        $this->staffUser = User::firstOrCreate(
            ['email' => 'admin@rlghobby.com'],
            ['name' => 'Admin Chief', 'user_type' => 'admin']
        );
    }

    public function test_unauthenticated_request_never_leaks_orders(): void
    {
        $response = $this->getJson('/api/customer-orders');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEmpty($response->json('data'));
        $this->assertEquals(0, $response->json('count'));
    }

    public function test_user_only_sees_their_own_orders_and_never_leaks_another_users_orders(): void
    {
        $processingStatus = RefOrderStatus::where('name', 'processing')->first();

        // Create order for User A
        $orderA = CustomerOrder::create([
            'order_number' => 'ORD-ALPHA-01',
            'order_date' => now(),
            'user_id' => $this->userA->id,
            'total_amount' => 1999.00,
            'payment_method' => 'GCASH',
            'payment_status' => 'Paid',
            'ref_order_status_id' => $processingStatus->id,
        ]);

        // Create order for User B
        $orderB = CustomerOrder::create([
            'order_number' => 'ORD-BRAVO-01',
            'order_date' => now(),
            'user_id' => $this->userB->id,
            'total_amount' => 4999.00,
            'payment_method' => 'MAYA',
            'payment_status' => 'Paid',
            'ref_order_status_id' => $processingStatus->id,
        ]);

        // When User A fetches orders with Sanctum token
        Sanctum::actingAs($this->userA);
        $responseA = $this->getJson('/api/customer-orders');

        $responseA->assertStatus(200);
        $orderNumbersA = collect($responseA->json('data'))->pluck('order_number')->all();
        $this->assertContains('ORD-ALPHA-01', $orderNumbersA);
        $this->assertNotContains('ORD-BRAVO-01', $orderNumbersA);

        // When User B fetches orders with Sanctum token
        Sanctum::actingAs($this->userB);
        $responseB = $this->getJson('/api/customer-orders');

        $responseB->assertStatus(200);
        $orderNumbersB = collect($responseB->json('data'))->pluck('order_number')->all();
        $this->assertContains('ORD-BRAVO-01', $orderNumbersB);
        $this->assertNotContains('ORD-ALPHA-01', $orderNumbersB);
    }

    public function test_user_cannot_access_another_users_order_detail(): void
    {
        $processingStatus = RefOrderStatus::where('name', 'processing')->first();

        $orderA = CustomerOrder::create([
            'order_number' => 'ORD-ALPHA-SECRET',
            'order_date' => now(),
            'user_id' => $this->userA->id,
            'total_amount' => 8888.00,
            'payment_method' => 'GCASH',
            'payment_status' => 'Paid',
            'ref_order_status_id' => $processingStatus->id,
        ]);

        // User B tries to view User A's order by ID
        Sanctum::actingAs($this->userB);
        $response = $this->getJson("/api/customer-orders/{$orderA->id}");

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Unauthorized access to order.');

        // User A views their own order successfully
        Sanctum::actingAs($this->userA);
        $ownResponse = $this->getJson("/api/customer-orders/{$orderA->id}");
        $ownResponse->assertStatus(200);
        $ownResponse->assertJsonPath('data.order_number', 'ORD-ALPHA-SECRET');
    }

    public function test_staff_can_view_all_orders(): void
    {
        Sanctum::actingAs($this->staffUser);
        $response = $this->getJson('/api/customer-orders');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(3, $response->json('count'));
    }

    public function test_unauthenticated_request_never_leaks_messages(): void
    {
        $response = $this->getJson('/api/customer-messages');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEmpty($response->json('data'));
        $this->assertEquals(0, $response->json('count'));
    }

    public function test_user_only_sees_their_own_messages_and_never_leaks_another_users_messages(): void
    {
        CustomerMessage::create([
            'user_id' => $this->userA->id,
            'subject' => 'User Alpha Inquiry',
            'message' => 'Private message from user A',
            'status' => 'ongoing',
        ]);

        CustomerMessage::create([
            'user_id' => $this->userB->id,
            'subject' => 'User Bravo Inquiry',
            'message' => 'Private message from user B',
            'status' => 'ongoing',
        ]);

        // User A fetches messages
        Sanctum::actingAs($this->userA);
        $responseA = $this->getJson('/api/customer-messages');

        $responseA->assertStatus(200);
        $subjectsA = collect($responseA->json('data'))->pluck('subject')->all();
        $this->assertContains('User Alpha Inquiry', $subjectsA);
        $this->assertNotContains('User Bravo Inquiry', $subjectsA);

        // User B fetches messages
        Sanctum::actingAs($this->userB);
        $responseB = $this->getJson('/api/customer-messages');

        $responseB->assertStatus(200);
        $subjectsB = collect($responseB->json('data'))->pluck('subject')->all();
        $this->assertContains('User Bravo Inquiry', $subjectsB);
        $this->assertNotContains('User Alpha Inquiry', $subjectsB);
    }

    public function test_customer_cannot_spoof_user_id_when_creating_message(): void
    {
        Sanctum::actingAs($this->userA);

        // User A tries to spoof user_id as User B
        $response = $this->postJson('/api/customer-messages', [
            'user_id' => $this->userB->id,
            'subject' => 'Spoof Attempt',
            'message' => 'Trying to post as user B',
            'status' => 'ongoing',
        ]);

        $response->assertStatus(201);
        // Backend must enforce authenticated user A ID
        $this->assertEquals($this->userA->id, $response->json('data.user_id'));
        $this->assertDatabaseHas('customer_message', [
            'subject' => 'Spoof Attempt',
            'user_id' => $this->userA->id,
        ]);
        $this->assertDatabaseMissing('customer_message', [
            'subject' => 'Spoof Attempt',
            'user_id' => $this->userB->id,
        ]);
    }

    public function test_staff_can_view_all_messages(): void
    {
        Sanctum::actingAs($this->staffUser);
        $response = $this->getJson('/api/customer-messages');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, $response->json('count'));
    }
}
