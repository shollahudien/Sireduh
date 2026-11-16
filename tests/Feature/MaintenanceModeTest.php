<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_normal_when_maintenance_disabled(): void
    {
        Setting::set('maintenance_mode', '0');

        $this->get('/')->assertOk();

        $this->get('/up')->assertOk();
    }

    public function test_public_gets_503_maintenance_page_when_enabled(): void
    {
        Setting::set('maintenance_mode', '1');
        Setting::set('maintenance_message', 'Upgrade server 23:00 WIB');

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Upgrade server 23:00 WIB')
            ->assertSee('Sedang dalam Perbaikan');

        $this->getJson('/api/availability?check_in=2027-01-01&check_out=2027-01-02')
            ->assertStatus(503)
            ->assertJsonPath('status', 'maintenance');
    }

    public function test_admin_can_login_and_use_app_during_maintenance(): void
    {
        Setting::set('maintenance_mode', '1');

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->get('/login')->assertOk(); // admin boleh membuka form login

        $this->withSession(['_token' => 'test-token'])->post('/login', [
            '_token' => 'test-token',
            'email' => $admin->email,
            'password' => 'secret',
        ])->assertRedirect('/admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_kasir_cannot_login_during_maintenance(): void
    {
        Setting::set('maintenance_mode', '1');

        $kasir = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);

        $response = $this->withSession(['_token' => 'test-token'])->post('/login', [
            '_token' => 'test-token',
            'email' => $kasir->email,
            'password' => 'secret',
        ]);
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_panel_shows_persistent_maintenance_banner_when_enabled(): void
    {
        Setting::set('maintenance_mode', '1');

        $admin = User::create([
            'name' => 'Admin Banner',
            'email' => 'adminban-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('MAINTENANCE AKTIF');
    }

    public function test_maintenance_page_does_not_show_admin_login_link(): void
    {
        Setting::set('maintenance_mode', '1');

        $this->get('/')
            ->assertStatus(503)
            ->assertDontSee('Masuk sebagai admin');
    }

    public function test_api_blocked_for_guests_but_not_for_admin_during_maintenance(): void
    {
        Setting::set('maintenance_mode', '1');

        $this->getJson('/api/products')->assertStatus(503);

        $admin = User::create([
            'name' => 'Admin API Test',
            'email' => 'adminapi-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->getJson('/api/admin/notifications')->assertOk();
    }
}
