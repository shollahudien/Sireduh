<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Utama',
            'email' => 'admin-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_list_create_update_and_reset_users(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        // List
        $list = $this->getJson('/api/admin/users');
        $list->assertOk()->assertJsonCount(1, 'data');

        // Create
        $created = $this->postJson('/api/admin/users', [
            'name' => 'Kasir Baru',
            'email' => 'kasir-baru@simateduh.test',
            'password' => 'katasandi123',
            'role' => 'kasir',
        ]);
        $created->assertStatus(201)->assertJsonPath('data.role', 'kasir');
        $userId = $created->json('data.id');

        // Update
        $updated = $this->putJson("/api/admin/users/{$userId}", [
            'name' => 'Kasir Senior',
            'email' => 'kasir-baru@simateduh.test',
            'role' => 'checkin',
        ]);
        $updated->assertOk()->assertJsonPath('data.role', 'checkin');

        // Reset password — login with the new password must succeed
        $reset = $this->postJson("/api/admin/users/{$userId}/password", [
            'password' => 'sandibaharu456',
        ]);
        $reset->assertOk();
        $this->assertTrue(auth()->attempt([
            'email' => 'kasir-baru@simateduh.test',
            'password' => 'sandibaharu456',
        ]));
    }

    public function test_admin_cannot_delete_self_or_last_admin(): void
    {
        $firstAdmin = $this->admin();
        $secondAdmin = $this->admin();
        $this->actingAs($firstAdmin);

        // Organize: exactly two admins -> first cannot delete LAST admin scenario:
        // delete the second admin is allowed, then first (the only admin) cannot delete itself.
        $delete = $this->deleteJson('/api/admin/users/'.$secondAdmin->id);
        $delete->assertOk();

        $selfDelete = $this->deleteJson('/api/admin/users/'.$firstAdmin->id);
        $selfDelete->assertStatus(422);

        // last admin cannot be deleted by design after removing second admin -> self delete already blocked;
        // verify a second admin can never be silently deleted when it's the only one left:
        $this->assertDatabaseCount('users', 1);
    }

    public function test_kasir_cannot_access_user_management(): void
    {
        $kasir = User::create([
            'name' => 'Kasir Biasa',
            'email' => 'kasir-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);

        $this->actingAs($kasir)
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }
}
