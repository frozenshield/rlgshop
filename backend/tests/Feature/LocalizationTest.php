<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test retrieving currencies.
     */
    public function test_can_get_currencies_list(): void
    {
        $response = $this->getJson('/api/ref-currencies');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertIsArray($response->json('data'));
        $this->assertGreaterThanOrEqual(10, count($response->json('data')));

        // Check PHP is present
        $codes = collect($response->json('data'))->pluck('code')->toArray();
        $this->assertContains('PHP', $codes);
        $this->assertContains('USD', $codes);
        $this->assertContains('JPY', $codes);
    }

    /**
     * Test retrieving weight units.
     */
    public function test_can_get_weight_units_list(): void
    {
        $response = $this->getJson('/api/ref-weight-units');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertIsArray($response->json('data'));
        $codes = collect($response->json('data'))->pluck('code')->toArray();
        $this->assertContains('kg_g', $codes);
        $this->assertContains('lb_oz', $codes);
    }

    /**
     * Test retrieving store localization settings.
     */
    public function test_can_get_store_localization_settings(): void
    {
        $response = $this->getJson('/api/localization');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'store_legal_name',
                    'brand_logo_url',
                    'currency_code',
                    'timezone',
                    'weight_unit_code',
                ],
            ]);

        $this->assertEquals('RLG Hobby Shop', $response->json('data.store_legal_name'));
        $this->assertEquals('PHP', $response->json('data.currency_code'));
    }

    /**
     * Test updating store localization settings.
     */
    public function test_can_update_store_localization_settings(): void
    {
        $payload = [
            'store_legal_name' => 'RLG Hobby & Collectibles International',
            'currency_code' => 'USD',
            'timezone' => 'Asia/Manila (GMT+8)',
            'weight_unit_code' => 'lb_oz',
        ];

        $response = $this->putJson('/api/localization', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.store_legal_name', 'RLG Hobby & Collectibles International')
            ->assertJsonPath('data.currency_code', 'USD')
            ->assertJsonPath('data.weight_unit_code', 'lb_oz');

        $this->assertDatabaseHas('store_localization', [
            'store_legal_name' => 'RLG Hobby & Collectibles International',
            'currency_code' => 'USD',
            'weight_unit_code' => 'lb_oz',
        ]);
    }
}
