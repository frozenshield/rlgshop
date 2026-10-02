<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartReminderMail;
use App\Models\AbandonedCartReminder;
use App\Models\Product;
use App\Models\ProductBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_product_bundles_can_be_retrieved_and_created(): void
    {
        $p1 = Product::create([
            'name' => 'One Piece OP-05 Booster Box',
            'sku' => 'TCG-OP-05-TEST',
            'price' => 3800,
            'stock' => 10,
            'description' => 'Booster box',
            'status' => 'active',
        ]);
        $p2 = Product::create([
            'name' => 'KMC Hyper Matte Sleeves',
            'sku' => 'SLV-KMC-TEST',
            'price' => 450,
            'stock' => 50,
            'description' => 'Card sleeves',
            'status' => 'active',
        ]);

        // Index will auto-seed if none exist, or we create one
        $bundle = ProductBundle::create([
            'primary_product_id' => $p1->id,
            'title' => 'Collector Protection Pack',
            'bundle_product_ids' => [$p2->id],
            'discount_percentage' => 10.00,
            'conversion_lift' => '+25%',
            'badge_text' => '🔥 Top Combo',
            'is_active' => true,
        ]);

        $res = $this->getJson('/api/product-bundles');
        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        // Create new bundle
        $createRes = $this->postJson('/api/product-bundles', [
            'primary_product_id' => $p1->id,
            'title' => 'Custom Master Bundle',
            'bundle_product_ids' => [$p2->id],
            'discount_percentage' => 15.00,
            'conversion_lift' => '+30%',
            'badge_text' => 'Pro Setup',
            'is_active' => true,
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // Toggle bundle
        $toggleRes = $this->postJson("/api/product-bundles/{$bundle->id}/toggle");
        $toggleRes->assertStatus(200)
            ->assertJsonPath('is_active', false);
    }

    public function test_abandoned_carts_lists_and_sends_recovery_email(): void
    {
        Mail::fake();

        $cart = AbandonedCartReminder::create([
            'customer_name' => 'Joshua Aquino',
            'customer_email' => 'joshua.hobby@gmail.com',
            'items_count' => 2,
            'total_value' => 5498.00,
            'cart_items' => [
                ['name' => 'Pokemon 151 ETB', 'price' => 3500, 'quantity' => 1],
            ],
            'recovery_token' => Str::random(40),
            'discount_code' => 'RECOVER10',
            'discount_percent' => 10,
            'reminder_sent' => false,
            'last_active_at' => now()->subHours(2),
        ]);

        // List
        $res = $this->getJson('/api/admin/abandoned-carts');
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['statistics', 'data']);

        // Send reminder
        $sendRes = $this->postJson("/api/admin/abandoned-carts/{$cart->id}/send-reminder");
        $sendRes->assertStatus(200)
            ->assertJsonPath('success', true);

        Mail::assertSent(AbandonedCartReminderMail::class, function ($mail) use ($cart) {
            return $mail->hasTo($cart->customer_email);
        });

        $this->assertDatabaseHas('abandoned_cart_reminders', [
            'id' => $cart->id,
            'reminder_sent' => true,
        ]);
    }

    public function test_cart_can_be_recovered_via_token(): void
    {
        $token = 'rec-token-12345';
        AbandonedCartReminder::create([
            'customer_name' => 'Mika Fernandez',
            'customer_email' => 'mika.otaku@yahoo.com',
            'items_count' => 1,
            'total_value' => 2688.00,
            'recovery_token' => $token,
            'discount_code' => 'RECOVER10',
            'discount_percent' => 10,
            'reminder_sent' => true,
            'last_active_at' => now()->subHours(5),
        ]);

        $res = $this->getJson("/api/cart/recover/{$token}");
        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer_name', 'Mika Fernandez')
            ->assertJsonPath('data.discount_code', 'RECOVER10');

        $this->assertDatabaseHas('abandoned_cart_reminders', [
            'recovery_token' => $token,
            'recovered' => true,
        ]);
    }

    public function test_seo_metadata_can_be_retrieved_and_ai_generated(): void
    {
        // Lookup default
        $res = $this->getJson('/api/seo-metadata');
        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        // Store SEO metadata
        $postRes = $this->postJson('/api/seo-metadata', [
            'entity_type' => 'global',
            'page_name' => 'Storefront Homepage Test',
            'route_path' => '/',
            'meta_title' => 'RLG Hobby Shop PH',
            'meta_description' => 'Collector shop in Manila Philippines',
            'focus_keyword' => 'Hobby Shop',
            'seo_score' => 95,
            'ai_generated' => true,
        ]);
        $postRes->assertStatus(201)
            ->assertJsonPath('success', true);

        // Route lookup
        $lookupRes = $this->getJson('/api/seo-metadata/lookup?route=/');
        $lookupRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.route_path', '/');

        // AI generate endpoint
        $aiRes = $this->postJson('/api/seo-metadata/generate-ai', [
            'page_name' => 'One Piece OP-05 Awakening Box',
            'entity_type' => 'product',
            'price' => 3800,
        ]);
        $aiRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['meta_title', 'meta_description', 'focus_keyword']]);
    }
}
