<?php

namespace Tests\Feature;

use App\Models\ShippingTax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test retrieving active shipping rates and VAT percentage.
     */
    public function test_can_get_active_shipping_tax(): void
    {
        $response = $this->getJson('/api/shipping-tax');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'standard_shipping_fee',
                    'free_shipping_threshold',
                    'vat_percentage',
                    'is_active',
                ],
            ]);

        $this->assertEquals(100.00, (float) $response->json('data.standard_shipping_fee'));
        $this->assertEquals(2500.00, (float) $response->json('data.free_shipping_threshold'));
        $this->assertEquals(12.00, (float) $response->json('data.vat_percentage'));
        $this->assertTrue((bool) $response->json('data.is_active'));
    }

    /**
     * Test updating shipping and VAT rules.
     */
    public function test_can_update_shipping_tax_settings(): void
    {
        $payload = [
            'standard_shipping_fee' => 120.00,
            'free_shipping_threshold' => 3000.00,
            'vat_percentage' => 12.00,
            'is_active' => true,
        ];

        $response = $this->putJson('/api/shipping-tax', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.standard_shipping_fee', '120.00')
            ->assertJsonPath('data.free_shipping_threshold', '3000.00')
            ->assertJsonPath('data.vat_percentage', '12.00');

        $this->assertDatabaseHas('shipping_tax', [
            'standard_shipping_fee' => 120.00,
            'free_shipping_threshold' => 3000.00,
            'vat_percentage' => 12.00,
        ]);
    }
}
