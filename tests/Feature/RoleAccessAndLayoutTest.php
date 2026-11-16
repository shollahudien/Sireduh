<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessAndLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_checkout_is_public_and_uses_the_public_layout(): void
    {
        $this->get('/checkout')
            ->assertOk()
            ->assertSee('data-layout="public"', false);
    }

    public function test_each_staff_role_is_redirected_to_its_own_workspace_after_login(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $checkin = User::factory()->create(['role' => User::ROLE_CHECKIN]);

        $this->withSession(['_token' => 'test-token'])->post('/login', ['_token' => 'test-token', 'email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin');
        $this->post('/logout');

        $this->withSession(['_token' => 'test-token'])->post('/login', ['_token' => 'test-token', 'email' => $kasir->email, 'password' => 'password'])
            ->assertRedirect('/pos');
        $this->post('/logout');

        $this->withSession(['_token' => 'test-token'])->post('/login', ['_token' => 'test-token', 'email' => $checkin->email, 'password' => 'password'])
            ->assertRedirect('/checkin');
    }

    public function test_staff_workspaces_reject_users_with_a_different_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);
        $checkin = User::factory()->create(['role' => User::ROLE_CHECKIN]);

        $this->actingAs($kasir)->get('/admin')->assertRedirect('/');
        $this->actingAs($checkin)->get('/pos')->assertRedirect('/');
        $this->actingAs($admin)->get('/checkin')->assertRedirect('/');
    }
}
