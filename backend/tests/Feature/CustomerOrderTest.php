<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\RefOrderStatus;
use App\Models\RefShippingCarrier;
use App\Models\User;
use Database\Seeders\CustomerOrderSeeder;
use Database\Seeders\RefOrderStatusSeeder;
use Database\Seeders\RefShippingCarrierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RefOrderStatusSeeder::class);
        $this->seed(RefShippingCarrierSeeder::class);
        $this->seed(CustomerOrderSeeder::class);
    }

    public function test_can_fetch_all_order_statuses(): void
    {
        $response = $this->getJson('/api/ref-order-statuses');

        $response->assertStatus(200);
        $response->assertJsonCount(8);

        $names = collect($response->json())->pluck('name')->all();
        $this->assertContains('pending', $names);
        $this->assertContains('processing', $names);
        $this->assertContains('shipped', $names);
        $this->assertContains('delivered', $names);
        $this->assertContains('accepted', $names);
        $this->assertContains('cancel', $names);
        $this->assertContains('refund', $names);
        $this->assertContains('return', $names);
    }

    public function test_can_fetch_all_shipping_carriers_from_image(): void
    {
        $response = $this->getJson('/api/ref-shipping-carriers');

        $response->assertStatus(200);
        $response->assertJsonCount(5);

        $names = collect($response->json())->pluck('name')->all();
        $this->assertContains('J&T Express Philippines', $names);
        $this->assertContains('LBC Express (Door to Door)', $names);
        $this->assertContains('Ninja Van Standard', $names);
        $this->assertContains('Flash Express Priority', $names);
        $this->assertContains('DHL Express International', $names);
    }

    public function test_can_list_customer_orders(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@rlghobby.com'],
            ['name' => 'Admin Chief', 'user_type' => 'admin']
        );

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/customer-orders');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertGreaterThanOrEqual(5, $response->json('count'));

        $firstOrder = $response->json('data.0');
        $this->assertArrayHasKey('order_number', $firstOrder);
        $this->assertArrayHasKey('status', $firstOrder);
        $this->assertArrayHasKey('items', $firstOrder);
    }

    public function test_can_filter_orders_by_status(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@rlghobby.com'],
            ['name' => 'Admin Chief', 'user_type' => 'admin']
        );

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/customer-orders?status=processing');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $order) {
            $this->assertEquals('processing', $order['status']['name']);
        }
    }

    public function test_can_create_new_order_with_items(): void
    {
        $processingStatus = RefOrderStatus::where('name', 'processing')->first();

        $payload = [
            'customer_name' => 'Ash Ketchum',
            'customer_email' => 'ash@pallettown.com',
            'customer_phone' => '+63 999 123 4567',
            'shipping_address' => 'Pallet Town Street 1',
            'city' => 'Kanto',
            'postal_code' => '1234',
            'payment_method' => 'GCash',
            'ref_order_status_id' => $processingStatus->id,
            'items' => [
                [
                    'product_name' => 'Charizard Ultra Premium Collection',
                    'sku' => 'TCG-PKM-UPC-CHAR',
                    'price' => 7499.00,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->postJson('/api/customer-orders', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertEquals('Ash Ketchum', $response->json('data.customer_profile.name'));
        $this->assertEquals(7499.00, (float) $response->json('data.total_amount'));

        $this->assertDatabaseHas('customer_orders', [
            'payment_method' => 'GCash',
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'name' => 'Ash Ketchum',
        ]);

        $this->assertDatabaseHas('customer_order_items', [
            'product_name' => 'Charizard Ultra Premium Collection',
            'sku' => 'TCG-PKM-UPC-CHAR',
        ]);
    }

    public function test_can_attach_fulfillment_with_carrier(): void
    {
        $order = CustomerOrder::where('order_number', 'ORD-9842')->first();
        $carrier = RefShippingCarrier::where('code', 'jnt_ph')->first();

        $payload = [
            'ref_shipping_carrier_id' => $carrier->id,
            'tracking_number' => 'PH-738428674',
            'mark_shipped' => true,
        ];

        $response = $this->postJson("/api/customer-orders/{$order->id}/fulfillment", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.fulfillment.tracking_number', 'PH-738428674');
        $response->assertJsonPath('data.fulfillment.carrier.name', 'J&T Express Philippines');

        // Verify order fulfillment table has the record
        $this->assertDatabaseHas('customer_order_fulfillments', [
            'customer_order_id' => $order->id,
            'ref_shipping_carrier_id' => $carrier->id,
            'tracking_number' => 'PH-738428674',
        ]);

        // Verify order status changed to shipped
        $shippedStatus = RefOrderStatus::where('name', 'shipped')->first();
        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'ref_order_status_id' => $shippedStatus->id,
        ]);
    }

    public function test_can_process_refund(): void
    {
        $order = CustomerOrder::where('order_number', 'ORD-9844')->first();

        $payload = [
            'refund_amount' => 7900.00,
            'is_full_refund' => true,
            'notes' => 'Customer requested cancellation before shipment.',
        ];

        $response = $this->postJson("/api/customer-orders/{$order->id}/refund", $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.refund_status', 'Full');
        $this->assertEquals(7900.00, (float) $response->json('data.refund_amount'));

        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'refund_status' => 'Full',
            'payment_status' => 'Refunded',
        ]);
    }
}
