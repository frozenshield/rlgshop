<?php

namespace Tests\Feature;

use App\Mail\StaticQrPaymentInstructionMail;
use App\Models\CustomerOrder;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Models\RefOrderStatus;
use App\Models\RefPaymentMerch;
use App\Models\User;
use Database\Seeders\RefOrderStatusSeeder;
use Database\Seeders\RefPaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaticQrPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RefOrderStatusSeeder::class);
        $this->seed(RefPaymentMethodSeeder::class);

        $this->customer = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'buyer@example.com',
        ]);

        $this->product = Product::create([
            'name' => 'Gundam RG 1/144',
            'sku' => 'GUN-RG-001',
            'price' => 1500.00,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
    }

    public function test_can_retrieve_active_qr_merchants(): void
    {
        $response = $this->getJson('/api/ref-payment-merchants');

        $response->assertStatus(200);
        $merchants = $response->json();
        $this->assertCount(4, $merchants);

        $codes = collect($merchants)->pluck('code')->toArray();
        $this->assertContains('gotyme', $codes);
        $this->assertContains('gcash', $codes);
        $this->assertContains('maribank', $codes);
        $this->assertContains('paymaya', $codes);
    }

    public function test_placing_order_with_static_qr_sends_instruction_email_and_sets_pending(): void
    {
        Mail::fake();

        $orderPayload = [
            'customer_name' => 'Juan Dela Cruz',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '09123456789',
            'shipping_address' => '123 Rizal Street',
            'city' => 'Makati',
            'postal_code' => '1200',
            'payment_method' => 'qr',
            'qr_merchant_code' => 'gotyme',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_name' => $this->product->name,
                    'price' => 1500.00,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/customer-orders', $orderPayload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.payment_status', 'Pending');

        $order = CustomerOrder::whereHas('customerProfile', function ($q) {
            $q->where('name', 'Juan Dela Cruz');
        })->firstOrFail();

        $this->assertStringContainsString('QR', $order->payment_method);
        $pendingStatus = RefOrderStatus::where('name', 'pending')->firstOrFail();
        $this->assertEquals($pendingStatus->id, $order->ref_order_status_id);

        Mail::assertSent(StaticQrPaymentInstructionMail::class, function ($mail) use ($order) {
            return $mail->hasTo('buyer@example.com')
                && $mail->order->id === $order->id
                && $mail->merchant->code === 'gotyme';
        });
    }

    public function test_email_view_renders_exact_buyer_instructions(): void
    {
        $profile = CustomerProfile::create([
            'user_id' => $this->customer->id,
            'name' => 'Juan Dela Cruz',
            'phone' => '09123456789',
            'address_line1' => '123 Rizal Street',
            'city' => 'Makati',
            'postal_code' => '1200',
        ]);

        $pendingStatus = RefOrderStatus::where('name', 'pending')->firstOrFail();

        $order = CustomerOrder::create([
            'order_number' => 'ORD-TEST-001',
            'order_date' => now(),
            'customer_profile_id' => $profile->id,
            'user_id' => $this->customer->id,
            'total_amount' => 1500.00,
            'payment_method' => 'QR - GoTyme Bank',
            'payment_status' => 'Pending',
            'ref_order_status_id' => $pendingStatus->id,
        ]);

        $merchant = RefPaymentMerch::where('code', 'gotyme')->firstOrFail();

        $mailable = new StaticQrPaymentInstructionMail($order, $merchant);

        $mailable->assertSeeInHtml('Scan this GoTyme Bank QR, input the exact total of');
        $mailable->assertSeeInHtml('₱1,500.00');
        $mailable->assertSeeInHtml('reply to this email with the payment screenshot');
    }
}
