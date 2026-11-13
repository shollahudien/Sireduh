<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogViewerTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_LOG = 'sireteduh-test.log';

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Log',
            'email' => 'adminlog-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    private function kasir(): User
    {
        return User::create([
            'name' => 'Kasir Log',
            'email' => 'kasirlog-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(storage_path('logs/'.self::TEST_LOG), implode("\n", [
            '[2026-11-12 10:00:00] local.INFO: Reservasi dibuat SIRES-TEST1',
            '[2026-11-12 10:01:00] local.ERROR: Fonnte gagal mengirim',
            '[2026-11-12 10:02:00] local.DEBUG: query 1ms',
            '[2026-11-12 10:03:00] local.WARNING: Retry attempt 1',
        ])."\n");
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('logs/'.self::TEST_LOG));

        parent::tearDown();
    }

    public function test_kasir_gets_403(): void
    {
        $this->actingAs($this->kasir())
            ->getJson('/api/admin/logs')
            ->assertStatus(403);
    }

    public function test_admin_can_read_logs_with_level_filter(): void
    {
        $this->actingAs($this->admin());

        $all = $this->getJson('/api/admin/logs/view?file='.self::TEST_LOG.'&lines=100&level=all');
        $all->assertOk()->assertJsonPath('success', true)->assertJsonCount(4, 'lines');

        $errors = $this->getJson('/api/admin/logs/view?file='.self::TEST_LOG.'&lines=100&level=error');
        $errors->assertOk()->assertJsonCount(1, 'lines');

        $search = $this->getJson('/api/admin/logs/view?file='.self::TEST_LOG.'&lines=100&level=all&search=Fonnte');
        $search->assertOk()->assertJsonCount(1, 'lines');
    }

    public function test_traversal_is_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->getJson('/api/admin/logs/view?file=..\/..\/.env&lines=10')->assertStatus(422);
        $this->getJson('/api/admin/logs/view?file=..%2F..%2F.env&lines=10')->assertStatus(422);
    }

    public function test_empty_log_file_returns_empty_list(): void
    {
        $this->actingAs($this->admin());

        file_put_contents(storage_path('logs/'.self::TEST_LOG), '');

        $this->getJson('/api/admin/logs/view?file='.self::TEST_LOG.'&lines=10&level=all')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'lines');
    }

    public function test_admin_can_clear_log_file(): void
    {
        $this->actingAs($this->admin());

        $this->postJson('/api/admin/logs/clear', ['file' => self::TEST_LOG])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('', file_get_contents(storage_path('logs/'.self::TEST_LOG)));
    }
}
