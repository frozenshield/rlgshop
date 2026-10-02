<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RefOrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_returns_metrics_from_stored_procedure(): void
    {
        $this->seed(RefOrderStatusSeeder::class);

        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'One Piece OP-05 Booster Box',
            'sku' => 'TCG-OP-05',
            'price' => 4500,
            'stock' => 8,
            'description' => 'Booster box',
            'status' => 'active',
        ]);

        // Create sample order
        CustomerOrder::create([
            'order_number' => 'ORD-1001',
            'order_date' => now(),
            'user_id' => $user->id,
            'total_amount' => 4500,
            'payment_method' => 'GCash',
            'payment_status' => 'paid',
            'ref_order_status_id' => 1, // Pending
        ]);

        // Insert cart item
        DB::table('customer_cart')->insert([
            'customer_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert favourite item
        DB::table('customer_favourites')->insert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/dashboard?timeframe=all');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'timeframe',
                'data' => [
                    'metrics' => [
                        'total_revenue',
                        'total_orders',
                        'average_order_value',
                        'total_added_cart',
                        'total_cart_unique_items',
                        'total_added_favourite',
                        'pending_orders_count',
                        'low_stock_count',
                        'unread_inquiries_count',
                        'active_visitors_today',
                        'revenue_growth_pct',
                        'order_velocity_pct',
                        'aov_bundle_lift',
                    ],
                    'top_products',
                    'top_referrers',
                    'recent_orders',
                ],
            ]);

        $this->assertEquals(4500, $response->json('data.metrics.total_revenue'));
        $this->assertEquals(1, $response->json('data.metrics.total_orders'));
        $this->assertEquals(2, $response->json('data.metrics.total_added_cart'));
        $this->assertEquals(1, $response->json('data.metrics.total_cart_unique_items'));
        $this->assertEquals(1, $response->json('data.metrics.total_added_favourite'));
        $this->assertEquals(1, $response->json('data.metrics.pending_orders_count'));
    }

    public function test_standalone_metrics_endpoint_executes_stored_procedure(): void
    {
        $response = $this->getJson('/api/admin/dashboard/metrics?timeframe=week');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'timeframe',
                'data' => [
                    'total_revenue',
                    'total_orders',
                    'average_order_value',
                    'total_added_cart',
                    'total_added_favourite',
                ],
            ]);
    }
}
