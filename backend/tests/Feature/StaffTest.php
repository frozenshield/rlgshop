<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test creating a staff member stores a hashed password and hides it in JSON.
     */
    public function test_staff_creation_hashes_password_and_hides_in_json(): void
    {
        $payload = [
            'name' => 'Security Officer',
            'email' => 'security@rlghobby.com',
            'password' => 'SecurePass2026!',
            'role_id' => 1,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/staff', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['password']);

        $staff = Staff::where('email', 'security@rlghobby.com')->first();
        $this->assertNotNull($staff);
        $this->assertNotEmpty($staff->getRawOriginal('password'));
        $this->assertTrue(Hash::check('SecurePass2026!', $staff->password));
    }

    /**
     * Test seeded staff members have passwords and can be verified.
     */
    public function test_seeded_staff_have_passwords(): void
    {
        $admin = Staff::where('email', 'admin@rlghobby.com')->first();
        $this->assertNotNull($admin);
        $this->assertNotEmpty($admin->getRawOriginal('password'));
        $this->assertTrue(Hash::check('AdminPass2026!', $admin->password));
    }

    /**
     * Test updating a staff member's password.
     */
    public function test_staff_update_allows_password_change(): void
    {
        $staff = Staff::where('email', 'darwin.pack@rlghobby.com')->first();
        $this->assertNotNull($staff);

        $response = $this->putJson("/api/staff/{$staff->id}", [
            'password' => 'NewFulfillmentPass99!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['password']);

        $staff->refresh();
        $this->assertTrue(Hash::check('NewFulfillmentPass99!', $staff->password));
    }

    /**
     * Test staff listing hides password attribute from JSON responses.
     */
    public function test_staff_index_does_not_expose_passwords(): void
    {
        $response = $this->getJson('/api/staff');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertArrayNotHasKey('password', $item);
        }
    }
}
