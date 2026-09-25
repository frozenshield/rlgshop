<?php

namespace Database\Seeders;

use App\Models\RefModule;
use Illuminate\Database\Seeder;

class RefModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'id' => 1,
                'name' => 'Dashboard',
                'code' => 'dashboard',
                'path' => '/admin/dashboard',
                'description' => 'Real-time sales revenue, quick stats, and performance overview.',
            ],
            [
                'id' => 2,
                'name' => 'Order Management',
                'code' => 'orders',
                'path' => '/admin/orders',
                'description' => 'Order dispatch, customer shipments, line items, and invoice management.',
            ],
            [
                'id' => 3,
                'name' => 'Inventory Control',
                'code' => 'inventory',
                'path' => '/admin/inventory',
                'description' => 'Real-time stock on hand, restock alerts, vendor lead times, and SKU counts.',
            ],
            [
                'id' => 4,
                'name' => 'Product Catalog',
                'code' => 'products',
                'path' => '/admin/products',
                'description' => 'Create, edit, AI analyze, and publish hobby merchandise listings.',
            ],
            [
                'id' => 5,
                'name' => 'CRM & Customers',
                'code' => 'customers',
                'path' => '/admin/customers',
                'description' => 'Customer profiles, order histories, tier rewards, and support logs.',
            ],
            [
                'id' => 6,
                'name' => 'Marketing & Promotions',
                'code' => 'marketing',
                'path' => '/admin/marketing',
                'description' => 'Discount coupons, promotional banners, and campaign flash sales.',
            ],
            [
                'id' => 7,
                'name' => 'CMS Publishing',
                'code' => 'cms',
                'path' => '/admin/cms',
                'description' => 'Manage homepage announcements, banners, policy pages, and rich content.',
            ],
            [
                'id' => 8,
                'name' => 'Analytics & Reports',
                'code' => 'analytics',
                'path' => '/admin/analytics',
                'description' => 'Financial reporting, margin breakdowns, top selling franchises, and exports.',
            ],
            [
                'id' => 9,
                'name' => 'Store Settings & Staff',
                'code' => 'settings',
                'path' => '/admin/settings',
                'description' => 'Staff RBAC management, currency, taxes, and shipping rates configuration.',
            ],
        ];

        foreach ($modules as $mod) {
            RefModule::updateOrCreate(
                ['id' => $mod['id']],
                $mod
            );
        }
    }
}
