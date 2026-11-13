<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ExternalApiTokenTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::create([
            'name' => 'Kasir External',
            'email' => 'kasir-ext-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);
    }

    public function test_external_client_can_login_and_use_token(): void
    {
        $user = $this->kasir();

        $login = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret',
            'device_name' => 'test-device',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.role', 'kasir')
            ->assertJsonPath('expires_in', 1440 * 60);

        $token = $login->json('access_token');

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me');

        $me->assertOk()->assertJsonPath('user.email', $user->email);
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        $user = $this->kasir();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'salah-banget',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = $this->kasir();

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        // Simulasikan request production: guard di-cache dalam kernel selama test,
        // jadi dikosongkan dulu agar autentikasi dievaluasi ulang dari DB.
        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->kasir();

        $token = $user->createToken('expired-test', ['*'], now()->subMinute())->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
