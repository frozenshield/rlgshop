<?php

namespace Database\Seeders;

use App\Models\RefStaffRole;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = RefStaffRole::where('name', 'Admin')->first();
        $managerRole = RefStaffRole::where('name', 'Store Manager')->first();
        $fulfillmentRole = RefStaffRole::where('name', 'Fulfillment Staff')->first();

        if (! $adminRole || ! $managerRole || ! $fulfillmentRole) {
            return;
        }

        $staffList = [
            [
                'name' => 'Admin Chief',
                'email' => 'admin@rlghobby.com',
                'password' => 'AdminPass2026!',
                'ref_staff_role_id' => $adminRole->id,
                'is_active' => true,
            ],
            [
                'name' => 'Rowena Santos',
                'email' => 'rowena.ops@rlghobby.com',
                'password' => 'AdminPass2026!',
                'ref_staff_role_id' => $managerRole->id,
                'is_active' => true,
            ],
            [
                'name' => 'Store Manager Demo',
                'email' => 'manager@rlghobby.com',
                'password' => 'AdminPass2026!',
                'ref_staff_role_id' => $managerRole->id,
                'is_active' => true,
            ],
            [
                'name' => 'Darwin Gomez',
                'email' => 'darwin.pack@rlghobby.com',
                'password' => 'AdminPass2026!',
                'ref_staff_role_id' => $fulfillmentRole->id,
                'is_active' => true,
            ],
            [
                'name' => 'Fulfillment Packer Demo',
                'email' => 'packer@rlghobby.com',
                'password' => 'AdminPass2026!',
                'ref_staff_role_id' => $fulfillmentRole->id,
                'is_active' => true,
            ],
        ];

        foreach ($staffList as $staff) {
            Staff::updateOrCreate(
                ['email' => $staff['email']],
                $staff
            );
        }
    }
}
