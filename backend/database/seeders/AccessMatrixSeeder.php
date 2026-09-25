<?php

namespace Database\Seeders;

use App\Models\AccessMatrix;
use App\Models\RefModule;
use App\Models\RefStaffRole;
use Illuminate\Database\Seeder;

class AccessMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = RefStaffRole::where('name', 'Admin')->first();
        $storeManager = RefStaffRole::where('name', 'Store Manager')->first();
        $fulfillmentStaff = RefStaffRole::where('name', 'Fulfillment Staff')->first();

        $modules = RefModule::all()->keyBy('code');

        if (! $admin || ! $storeManager || ! $fulfillmentStaff || $modules->isEmpty()) {
            return;
        }

        $matrix = [];

        // 1. ADMIN (Super Admin) - Has Unrestricted Access to ALL Modules (Read, Create, Update, Delete)
        foreach ($modules as $mod) {
            $matrix[] = [
                'role_id' => $admin->id,
                'module_id' => $mod->id,
                'sub_module_id' => null,
                'can_read' => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ];
        }

        // 2. STORE MANAGER - Catalog & Operations
        // Permissions: Order Management, Inventory Control, CRM Support, Promotions, Products, Analytics
        $managerPermissions = [
            'dashboard' => ['read' => true, 'create' => false, 'update' => false, 'delete' => false],
            'orders' => ['read' => true, 'create' => false, 'update' => true, 'delete' => false],
            'inventory' => ['read' => true, 'create' => true, 'update' => true, 'delete' => false],
            'products' => ['read' => true, 'create' => true, 'update' => true, 'delete' => false],
            'customers' => ['read' => true, 'create' => false, 'update' => true, 'delete' => false],
            'marketing' => ['read' => true, 'create' => true, 'update' => true, 'delete' => true],
            'cms' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
            'analytics' => ['read' => true, 'create' => false, 'update' => false, 'delete' => false],
            'settings' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
        ];

        foreach ($managerPermissions as $code => $perms) {
            if (isset($modules[$code])) {
                $matrix[] = [
                    'role_id' => $storeManager->id,
                    'module_id' => $modules[$code]->id,
                    'sub_module_id' => null,
                    'can_read' => $perms['read'],
                    'can_create' => $perms['create'],
                    'can_update' => $perms['update'],
                    'can_delete' => $perms['delete'],
                ];
            }
        }

        // 3. FULFILLMENT STAFF - Packing & Shipping Only
        // Permissions: View Orders, Print Packing Slips, Update Shipping Status
        $fulfillmentPermissions = [
            'dashboard' => ['read' => true, 'create' => false, 'update' => false, 'delete' => false],
            'orders' => ['read' => true, 'create' => false, 'update' => true, 'delete' => false], // update shipping/packing status
            'inventory' => ['read' => true, 'create' => false, 'update' => false, 'delete' => false], // view stock count only
            'products' => ['read' => true, 'create' => false, 'update' => false, 'delete' => false], // view product details only
            'customers' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
            'marketing' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
            'cms' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
            'analytics' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
            'settings' => ['read' => false, 'create' => false, 'update' => false, 'delete' => false],
        ];

        foreach ($fulfillmentPermissions as $code => $perms) {
            if (isset($modules[$code])) {
                $matrix[] = [
                    'role_id' => $fulfillmentStaff->id,
                    'module_id' => $modules[$code]->id,
                    'sub_module_id' => null,
                    'can_read' => $perms['read'],
                    'can_create' => $perms['create'],
                    'can_update' => $perms['update'],
                    'can_delete' => $perms['delete'],
                ];
            }
        }

        foreach ($matrix as $row) {
            AccessMatrix::updateOrCreate(
                [
                    'role_id' => $row['role_id'],
                    'module_id' => $row['module_id'],
                    'sub_module_id' => $row['sub_module_id'],
                ],
                $row
            );
        }
    }
}
