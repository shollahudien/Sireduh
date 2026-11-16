<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationCancellationService;
use Illuminate\Console\Command;

class ExpirePendingReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire-pending {--hours=1 : Batas waktu (jam) reservasi pending sebelum dibatalkan otomatis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan reservasi pending yang belum dibayar melewati batas waktu dan kembalikan stok produk tambahan';

    /**
     * Execute the console command.
     */
    public function handle(ReservationCancellationService $cancellations): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $expiredIds = Reservation::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        $cancelled = 0;
        foreach ($expiredIds as $id) {
            $reservation = Reservation::query()->find($id);

            if ($reservation !== null && $cancellations->cancel($reservation, ['pending'])) {
                $cancelled++;
            }
        }

        $this->info("Reservasi pending kedaluwarsa dibatalkan: {$cancelled} (batas {$hours} jam).");

        return self::SUCCESS;
    }
}
