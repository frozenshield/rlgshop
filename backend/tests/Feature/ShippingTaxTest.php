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
     * Test retrieving all shipping and VAT rules.
     */
    public function test_can_get_all_shipping_tax_rules(): void
    {
        $response = $this->getJson('/api/shipping-tax?all=1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertIsArray($response->json('data'));
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /**
     * Test storing a new shipping and tax rule row.
     */
    public function test_can_store_new_shipping_tax_rule(): void
    {
        $payload = [
            'name' => 'Metro Manila Same-Day Express',
            'standard_shipping_fee' => 180.00,
            'free_shipping_threshold' => 3500.00,
            'vat_percentage' => 12.00,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/shipping-tax', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Metro Manila Same-Day Express')
            ->assertJsonPath('data.standard_shipping_fee', '180.00');

        $this->assertDatabaseHas('shipping_tax', [
            'name' => 'Metro Manila Same-Day Express',
            'standard_shipping_fee' => 180.00,
            'free_shipping_threshold' => 3500.00,
            'vat_percentage' => 12.00,
        ]);
    }

    /**
     * Test toggling rule status between active and inactive.
     */
    public function test_can_toggle_shipping_tax_rule_status(): void
    {
        $rule = ShippingTax::first();
        $this->assertTrue((bool) $rule->is_active);

        $response = $this->patchJson("/api/shipping-tax/{$rule->id}/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse((bool) $rule->fresh()->is_active);
    }

    /**
     * Test deleting a shipping and tax rule row.
     */
    public function test_can_delete_shipping_tax_rule(): void
    {
        $rule = ShippingTax::create([
            'name' => 'Temporary Fee Rule',
            'standard_shipping_fee' => 50.00,
            'free_shipping_threshold' => 1000.00,
            'vat_percentage' => 0.00,
            'is_active' => false,
        ]);

        $response = $this->deleteJson("/api/shipping-tax/{$rule->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('shipping_tax', [
            'id' => $rule->id,
        ]);
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
