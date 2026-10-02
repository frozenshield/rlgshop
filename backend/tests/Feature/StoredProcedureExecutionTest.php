<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoredProcedureExecutionTest extends TestCase
{
    public function test_mysql_stored_procedure_executes_if_mysql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Skipping MySQL stored procedure test on non-mysql driver');
        }

        $results = DB::select('CALL sp_get_admin_dashboard_metrics(?)', ['week']);
        $this->assertNotEmpty($results);
        $row = (array) $results[0];

        $this->assertArrayHasKey('total_revenue', $row);
        $this->assertArrayHasKey('total_orders', $row);
        $this->assertArrayHasKey('average_order_value', $row);
        $this->assertArrayHasKey('total_added_cart', $row);
        $this->assertArrayHasKey('total_added_favourite', $row);
        $this->assertArrayHasKey('pending_orders_count', $row);
        $this->assertArrayHasKey('low_stock_count', $row);
        $this->assertArrayHasKey('unread_inquiries_count', $row);
    }
}
