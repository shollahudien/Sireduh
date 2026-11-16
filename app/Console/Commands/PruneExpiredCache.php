<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExpiredCache extends Command
{
    protected $signature = 'cache:prune-expired';

    protected $description = 'Hapus entri cache database yang sudah kedaluwarsa';

    public function handle(): int
    {
        $table = config('cache.stores.database.table', 'cache');

        try {
            $deleted = DB::table($table)->where('expiration', '<=', now()->timestamp)->delete();
            $this->info("Cache kedaluwarsa dihapus: {$deleted} entri.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->warn('Gagal prune cache database: '.$e->getMessage());

            return self::SUCCESS;
        }
    }
}
