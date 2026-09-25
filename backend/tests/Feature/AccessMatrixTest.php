<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test retrieving the full access matrix rules.
     */
    public function test_access_matrix_index_returns_list(): void
    {
        $response = $this->getJson('/api/access-matrix');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'count',
                'data',
            ]);
    }

    /**
     * Test admin role receives all 9 modules in staff-modules API.
     */
    public function test_staff_modules_for_admin_returns_all_nine_modules(): void
    {
        $response = $this->getJson('/api/staff-modules?role=super-admin');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('role.name', 'Admin');

        $codes = $response->json('allowed_codes');
        $this->assertCount(9, $codes);
        $this->assertContains('dashboard', $codes);
        $this->assertContains('orders', $codes);
        $this->assertContains('inventory', $codes);
        $this->assertContains('products', $codes);
        $this->assertContains('customers', $codes);
        $this->assertContains('marketing', $codes);
        $this->assertContains('cms', $codes);
        $this->assertContains('analytics', $codes);
        $this->assertContains('settings', $codes);
    }

    /**
     * Test store manager role excludes cms and settings from allowed modules.
     */
    public function test_staff_modules_for_store_manager_excludes_cms_and_settings(): void
    {
        $response = $this->getJson('/api/staff-modules?role=manager');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('role.name', 'Store Manager');

        $codes = $response->json('allowed_codes');
        $this->assertCount(7, $codes);
        $this->assertContains('dashboard', $codes);
        $this->assertContains('orders', $codes);
        $this->assertContains('inventory', $codes);
        $this->assertContains('products', $codes);
        $this->assertContains('customers', $codes);
        $this->assertContains('marketing', $codes);
        $this->assertContains('analytics', $codes);
        $this->assertNotContains('cms', $codes);
        $this->assertNotContains('settings', $codes);
    }

    /**
     * Test fulfillment staff role only receives 4 operational modules.
     */
    public function test_staff_modules_for_fulfillment_returns_only_four_modules(): void
    {
        $response = $this->getJson('/api/staff-modules?role=fulfillment');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('role.name', 'Fulfillment Staff');

        $codes = $response->json('allowed_codes');
        $this->assertCount(4, $codes);
        $this->assertContains('dashboard', $codes);
        $this->assertContains('orders', $codes);
        $this->assertContains('inventory', $codes);
        $this->assertContains('products', $codes);
        $this->assertNotContains('customers', $codes);
        $this->assertNotContains('marketing', $codes);
        $this->assertNotContains('cms', $codes);
        $this->assertNotContains('analytics', $codes);
        $this->assertNotContains('settings', $codes);
    }

    /**
     * Test resolving staff modules by staff model route binding.
     */
    public function test_staff_modules_by_staff_id(): void
    {
        $staff = Staff::where('email', 'darwin.pack@rlghobby.com')->first();
        $this->assertNotNull($staff);

        $response = $this->getJson("/api/staff/{$staff->id}/modules");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('staff.email', 'darwin.pack@rlghobby.com')
            ->assertJsonPath('role.name', 'Fulfillment Staff');

        $codes = $response->json('allowed_codes');
        $this->assertCount(4, $codes);
    }

    /**
     * Test resolving staff modules by staff email parameter.
     */
    public function test_staff_modules_by_email(): void
    {
        $response = $this->getJson('/api/staff-modules?email=rowena.ops@rlghobby.com');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('staff.email', 'rowena.ops@rlghobby.com')
            ->assertJsonPath('role.name', 'Store Manager');

        $codes = $response->json('allowed_codes');
        $this->assertCount(7, $codes);
    }
}
