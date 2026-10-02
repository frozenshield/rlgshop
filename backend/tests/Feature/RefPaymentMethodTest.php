<?php

namespace Tests\Feature;

use App\Models\RefPaymentMethod;
use Database\Seeders\RefPaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RefPaymentMethodSeeder::class);
    }

    public function test_ref_payment_method_table_has_required_schema(): void
    {
        $this->assertTrue(Schema::hasTable('ref_payment_method'));
        $this->assertTrue(Schema::hasColumns('ref_payment_method', [
            'id',
            'name',
            'code',
            'label',
            'status',
            'description',
            'icon',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_contains_all_specified_payment_methods(): void
    {
        $expected = [
            'qr',
            'stripe',
            'paypal',
            'visa/master card',
            'cash on delivery',
        ];

        foreach ($expected as $methodName) {
            $this->assertDatabaseHas('ref_payment_method', [
                'name' => $methodName,
                'status' => 'active',
            ]);
        }

        $this->assertEquals(5, RefPaymentMethod::count());
    }

    public function test_can_fetch_active_payment_methods_via_api(): void
    {
        $response = $this->getJson('/api/ref-payment-methods');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json());
        $response->assertJsonFragment(['name' => 'qr', 'status' => 'active']);
        $response->assertJsonFragment(['name' => 'cash on delivery', 'status' => 'active']);
        $response->assertJsonFragment(['name' => 'visa/master card', 'status' => 'active']);
    }

    public function test_can_fetch_active_payment_merchants_via_api(): void
    {
        $response = $this->getJson('/api/ref-payment-merchants');

        $response->assertStatus(200);
        $this->assertCount(4, $response->json());
        $response->assertJsonFragment(['code' => 'gotyme']);
        $response->assertJsonFragment(['code' => 'gcash']);
        $response->assertJsonFragment(['code' => 'maribank']);
        $response->assertJsonFragment(['code' => 'paymaya']);
    }

    public function test_can_update_payment_method_status_via_api(): void
    {
        $method = RefPaymentMethod::where('name', 'paypal')->firstOrFail();
        $this->assertEquals('active', $method->status);

        // Update status to inactive
        $response = $this->patchJson("/api/ref-payment-methods/{$method->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('ref_payment_method', [
            'id' => $method->id,
            'status' => 'inactive',
        ]);

        // Default list only returns active payment methods
        $listResponse = $this->getJson('/api/ref-payment-methods');
        $listResponse->assertStatus(200);
        $this->assertCount(4, $listResponse->json());
        $listResponse->assertJsonMissing(['name' => 'paypal']);

        // Querying with status=all returns all methods including inactive
        $allResponse = $this->getJson('/api/ref-payment-methods?status=all');
        $allResponse->assertStatus(200);
        $this->assertCount(5, $allResponse->json());
        $allResponse->assertJsonFragment(['name' => 'paypal', 'status' => 'inactive']);
    }

    public function test_rejects_invalid_status(): void
    {
        $method = RefPaymentMethod::where('name', 'qr')->firstOrFail();

        $response = $this->patchJson("/api/ref-payment-methods/{$method->id}/status", [
            'status' => 'archived',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
