<?php

namespace App\Console\Commands;

use App\Services\LogPruneService;
use Illuminate\Console\Command;

class PruneLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune {--days= : Override the stored retention (0 = disabled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus log file (*.log) yang lebih lama dari retensi (pengaturan log_retention_days)';

    /**
     * Execute the console command.
     */
    public function handle(LogPruneService $pruner): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : $pruner->retentionDays();

        if ($days <= 0) {
            $this->info('Retensi file log nonaktif (log_retention_days = 0).');

            return self::SUCCESS;
        }

        $result = $pruner->prune($days);

        $rows = [];
        foreach ($result['results'] as $row) {
            $rows[] = [
                $row['file'],
                $row['skipped'] ? 'skip ('.$row['reason'].')' : "{$row['removed']} entri dihapus, {$row['kept']} entri tersisa",
            ];
        }

        if ($rows !== []) {
            $this->table(['File', 'Hasil'], $rows);
        }

        $this->info("Selesai. Total {$result['deleted_total']} entri log lama dihapus.");

        return self::SUCCESS;
    }
}
