<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\LogPruneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LogPruneTest extends TestCase
{
    use RefreshDatabase;

    private string $logDir;

    private const TEST_LOG = 'sireteduh-prune-test.log';

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Prune',
            'email' => 'adminprune-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = storage_path('framework/testing/logs-'.uniqid());
        mkdir($this->logDir, 0775, true);

        // Semua file log di test seretnga di direktori temp — bukan storage/logs asli
        $this->app->instance(LogPruneService::class, new LogPruneService($this->logDir));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->logDir)) {
            foreach (glob($this->logDir.'/*.log') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->logDir);
        }

        parent::tearDown();
    }

    private function seedLog(): void
    {
        file_put_contents($this->logDir.'/'.self::TEST_LOG, implode(PHP_EOL, [
            '['.now()->subDays(10)->format('Y-m-d H:i:s').'] local.ERROR: crash lama',
            '#0 /app/StackTest.php(10): stackTraceOld',
            '['.now()->subDay()->format('Y-m-d H:i:s').'] local.INFO: reservasi baru',
            'detail reservasi baru',
        ]).PHP_EOL);
    }

    public function test_prune_removes_old_entries_including_stack_traces(): void
    {
        $this->seedLog();

        $response = $this->actingAs($this->admin())
            ->putJson('/api/admin/logs/retention', ['days' => 3]);

        $response->assertOk()->assertJsonPath('success', true);

        $content = file_get_contents($this->logDir.'/'.self::TEST_LOG);
        $this->assertStringNotContainsString('crash lama', $content);
        $this->assertStringNotContainsString('stackTraceOld', $content);
        $this->assertStringContainsString('reservasi baru', $content);
        $this->assertStringContainsString('detail reservasi baru', $content);
        $this->assertSame('3', Setting::get('log_retention_days'));
    }

    public function test_retention_zero_deletes_nothing(): void
    {
        $this->seedLog();

        $before = file_get_contents($this->logDir.'/'.self::TEST_LOG);

        $this->actingAs($this->admin())
            ->putJson('/api/admin/logs/retention', ['days' => 0])
            ->assertOk()
            ->assertJsonPath('deleted', 0);

        $this->assertSame($before, file_get_contents($this->logDir.'/'.self::TEST_LOG));
    }

    public function test_artisan_logs_prune_honors_stored_retention(): void
    {
        Setting::set('log_retention_days', '3');
        $this->seedLog();

        Artisan::call('logs:prune');

        $content = file_get_contents($this->logDir.'/'.self::TEST_LOG);
        $this->assertStringNotContainsString('crash lama', $content);
        $this->assertStringContainsString('reservasi baru', $content);
    }

    public function test_invalid_retention_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->putJson('/api/admin/logs/retention', ['days' => 14])
            ->assertStatus(422);
    }

    public function test_prune_works_on_empty_log_file(): void
    {
        file_put_contents($this->logDir.'/'.self::TEST_LOG, '');

        $this->actingAs($this->admin())
            ->putJson('/api/admin/logs/retention', ['days' => 3])
            ->assertOk();

        $this->assertSame('', file_get_contents($this->logDir.'/'.self::TEST_LOG));
    }
}
