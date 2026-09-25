<?php

namespace Database\Seeders;

use App\Models\RefStaffRole;
use Illuminate\Database\Seeder;

class RefStaffRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'name' => 'Admin',
                'label' => 'Super Admin (Unrestricted)',
                'permissions_label' => 'All Modules, Financial Refunds, Staff Management, CMS Publishing',
                'description' => 'Unrestricted root-level administrative access to all shop functions, financial tools, and system settings.',
            ],
            [
                'id' => 2,
                'name' => 'Store Manager',
                'label' => 'Store Manager (Catalog & Operations)',
                'permissions_label' => 'Order Management, Inventory Control, CRM Support, Promotions',
                'description' => 'Manages day-to-day catalog, product inventory adjustments, customer communications, and promotional campaigns.',
            ],
            [
                'id' => 3,
                'name' => 'Fulfillment Staff',
                'label' => 'Fulfillment Staff (Packing & Shipping only)',
                'permissions_label' => 'View Orders, Print Packing Slips, Update Shipping Status',
                'description' => 'Packing, shipping, viewing order line items, printing slips, and dispatching couriers.',
            ],
        ];

        foreach ($roles as $role) {
            RefStaffRole::updateOrCreate(
                ['id' => $role['id']],
                $role
            );
        }
    }
}
