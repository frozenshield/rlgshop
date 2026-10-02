<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\RefCategory;
use App\Models\RefOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic reference order status
        RefOrderStatus::firstOrCreate(['id' => 1], ['name' => 'Pending', 'label' => 'Pending']);
        RefOrderStatus::firstOrCreate(['id' => 3], ['name' => 'Paid', 'label' => 'Paid']);
        RefOrderStatus::firstOrCreate(['id' => 6], ['name' => 'Cancelled', 'label' => 'Cancelled']);

        RefCategory::firstOrCreate(['id' => 1], ['desc' => 'Trading Card Games']);
        RefCategory::firstOrCreate(['id' => 2], ['desc' => 'Action Figures']);
    }

    public function test_admin_analytics_report_returns_expected_structure(): void
    {
        // Create test product and order
        $product = Product::create([
            'name' => 'One Piece OP-05 Booster Box',
            'sku' => 'TCG-OP-05',
            'stock' => 12,
            'price' => 4500.00,
            'status' => 'active',
            'ref_category_id' => 1,
        ]);

        CustomerOrder::create([
            'order_number' => 'ORD-TEST-001',
            'order_date' => now(),
            'total_amount' => 4500.00,
            'payment_method' => 'GCash',
            'payment_status' => 'Paid',
            'ref_order_status_id' => 3,
            'refund_status' => 'None',
            'refund_amount' => 0.00,
        ]);

        $response = $this->getJson('/api/admin/analytics/report?timeframe=30d');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('timeframe', '30d')
            ->assertJsonStructure([
                'success',
                'timeframe',
                'period_label',
                'generated_at',
                'currency',
                'kpis' => [
                    'gross_sales',
                    'total_refunds',
                    'net_store_sales',
                    'vat_collected',
                    'est_logistics_expense',
                    'total_orders',
                    'average_order_value',
                    'tied_up_capital',
                    'total_inventory_units',
                    'low_stock_count',
                    'out_of_stock_count',
                    'total_active_skus',
                ],
                'monthlyTrajectory',
                'highVelocityItems',
                'deadStockItems',
                'categoryDistribution',
                'paymentGatewayDistribution',
            ]);
    }

    public function test_admin_analytics_report_supports_different_timeframes(): void
    {
        foreach (['7d', '30d', '90d', 'year', 'all'] as $period) {
            $response = $this->getJson("/api/admin/analytics/report?timeframe={$period}");
            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('timeframe', $period);
        }
    }

    public function test_admin_analytics_export_generates_formatted_excel_file(): void
    {
        $response = $this->get('/api/admin/analytics/export?timeframe=30d');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.ms-excel', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('RLG_Executive_Analytics_Report_30d', (string) $response->headers->get('content-disposition'));

        $content = $response->getContent();
        $this->assertStringContainsString('RLG TOYS &amp; HOBBIES &bull; EXECUTIVE ANALYTICS &amp; FINANCIAL AUDIT REPORT', $content);
        $this->assertStringContainsString('1. EXECUTIVE FINANCIAL OVERVIEW', $content);
        $this->assertStringContainsString('2. SIX-MONTH HISTORICAL GROSS VS NET SALES TRAJECTORY', $content);
        $this->assertStringContainsString('3. HIGH VELOCITY ITEMS &bull; STOCKOUT RISK MATRIX', $content);
        $this->assertStringContainsString('4. DEAD STOCK &amp; STAGNANT CAPITAL AUDIT', $content);
        $this->assertStringContainsString('5. PRODUCT CATEGORY REVENUE &amp; DISTRIBUTION BREAKDOWN', $content);
        $this->assertStringContainsString('6. PAYMENT GATEWAYS &amp; SETTLEMENT SHARE', $content);
        $this->assertStringContainsString('border: 1px solid #cbd5e1', $content);
        $this->assertStringContainsString('mso-number-format', $content);
    }
}
