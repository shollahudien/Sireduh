<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Retensi',
            'email' => 'adminret-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    private function insertRow(string $path, string $createdAt): void
    {
        DB::table('activity_logs')->insert([
            'ip' => '127.0.0.1',
            'method' => 'GET',
            'path' => $path,
            'status' => 200,
            'created_at' => $createdAt,
        ]);
    }

    public function test_retention_change_deletes_old_rows_but_keeps_recent(): void
    {
        $this->insertRow('/old-page', now()->subDays(10)->toDateTimeString());
        $this->insertRow('/new-page', now()->subDay()->toDateTimeString());

        $response = $this->actingAs($this->admin())
            ->putJson('/api/admin/activity/retention', ['days' => 3]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('activity_logs', ['path' => '/old-page']);
        $this->assertDatabaseHas('activity_logs', ['path' => '/new-page']);
        $this->assertSame('3', Setting::get('activity_retention_days'));
    }

    public function test_retention_zero_disables_deletion(): void
    {
        $this->insertRow('/old-page', now()->subDays(400)->toDateTimeString());

        $response = $this->actingAs($this->admin())
            ->putJson('/api/admin/activity/retention', ['days' => 0]);

        $response->assertOk();
        $this->assertDatabaseHas('activity_logs', ['path' => '/old-page']);
    }

    public function test_artisan_prune_command_uses_stored_retention(): void
    {
        Setting::set('activity_retention_days', '3');

        $this->insertRow('/old-page', now()->subDays(10)->toDateTimeString());

        Artisan::call('activity:prune');

        $this->assertDatabaseMissing('activity_logs', ['path' => '/old-page']);
    }

    public function test_invalid_retention_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->putJson('/api/admin/activity/retention', ['days' => 14])
            ->assertStatus(422);
    }
}
