<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visit_is_logged(): void
    {
        $this->get('/')->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'method' => 'GET',
            'path' => '/',
            'status' => 200,
            'user_id' => null,
        ]);
    }

    public function test_authenticated_admin_visit_is_logged_with_user_and_role(): void
    {
        $admin = User::create([
            'name' => 'Admin Aktivitas',
            'email' => 'adminact-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'user_role' => 'admin',
            'path' => 'admin',
            'status' => 200,
        ]);
    }

    public function test_polling_and_static_assets_are_not_logged(): void
    {
        $admin = User::create([
            'name' => 'Admin Poll',
            'email' => 'adminpoll-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $before = ActivityLog::count();

        $this->actingAs($admin)->getJson('/api/admin/notifications')->assertOk();
        $this->get('/favicon.ico');

        $this->assertSame($before, ActivityLog::count());
    }

    public function test_admin_can_clear_activity_log(): void
    {
        ActivityLog::create([
            'ip' => '127.0.0.1',
            'method' => 'GET',
            'path' => '/',
            'status' => 200,
        ]);

        $admin = User::create([
            'name' => 'Admin Clear',
            'email' => 'adminclear-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/activity/clear')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('activity_logs', 1); // baris log request itu sendiri boleh tersimpan, sisanya terhapus
    }
}
