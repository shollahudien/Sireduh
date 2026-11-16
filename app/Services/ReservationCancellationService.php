<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class ReservationCancellationService
{
    /**
     * Status yang boleh dibatalkan (manual admin maupun kadaluwarsa otomatis).
     *
     * @var list<string>
     */
    private const CANCELLABLE_STATUSES = ['pending', 'waiting_verification', 'paid', 'confirmed'];

    /**
     * Batalkan reservasi dan kembalikan stok produk tambahan yang dipotong
     * saat pesanan dibuat. Slot unit tidak perlu dikembalikan karena
     * ketersediaan unit dihitung dari reservasi berstatus aktif
     * (lihat AvailabilityService::BLOCKING_STATUSES), bukan dari kolom stock.
     */
    public function cancel(Reservation $reservation, ?array $allowedStatuses = null): bool
    {
        $allowed = $allowedStatuses ?? self::CANCELLABLE_STATUSES;

        return DB::transaction(function () use ($reservation, $allowed): bool {
            $locked = Reservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! in_array($locked->status, $allowed, true)) {
                return false;
            }

            $locked->update(['status' => 'cancelled']);

            // Tandai pembayaran terkait sebagai ditolak agar tidak lagi muncul
            // di antrean verifikasi (bell badge & halaman verifikasi).
            $locked->payment()->update(['status' => 'rejected']);

            foreach ($locked->addons()->with('product')->get() as $addon) {
                if ($addon->product !== null) {
                    $addon->product->increment('stock', $addon->quantity);
                }
            }

            return true;
        });
    }
}
