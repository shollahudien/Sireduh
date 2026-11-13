<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:prune {--days= : Override the stored retention (0 = disabled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus activity log yang lebih lama dari retensi yang diatur (pengaturan activity_retention_days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) Setting::get('activity_retention_days', '30');

        if ($days <= 0) {
            $this->info('Retensi otomatis nonaktif (activity_retention_days = 0). Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        $deleted = ActivityLog::pruneOlderThan($days);

        $this->info("Menghapus {$deleted} entri activity log yang lebih tua dari {$days} hari.");

        return self::SUCCESS;
    }
}
