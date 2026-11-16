<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendETicketJob;
use App\Models\Reservation;
use App\Services\ReservationCancellationService;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::with(['visitor', 'unit', 'ticket'])
            ->latest()
            ->paginate(20);

        return view('admin.reservations.index', compact('reservations'));
    }

    public function verify(Reservation $reservation)
    {
        // If already paid/confirmed, just return
        if (in_array($reservation->status, ['paid', 'confirmed'])) {
            return back()->with('info', 'Reservasi sudah diverifikasi.');
        }

        // Use atomic transaction for verification
        $reservation = Reservation::lockForUpdate()->find($reservation->id);

        $reservation->status = 'paid';
        $reservation->save();

        // Trigger WhatsApp E-Ticket queue job
        SendETicketJob::dispatchSync($reservation->id);

        return back()->with('success', 'Reservasi diverifikasi dan E-ticket WhatsApp terkirim.');
    }

    public function cancel(Reservation $reservation, ReservationCancellationService $cancellations)
    {
        // Stok unit tidak dikembalikan ke kolom stock: ketersediaan unit dihitung
        // dari reservasi aktif. Stok produk tambahan yang dipotong saat pesanan
        // dibuat memang harus dikembalikan oleh service.
        $cancelled = $cancellations->cancel($reservation);

        return back()->with(
            $cancelled ? 'success' : 'info',
            $cancelled
                ? 'Reservasi dibatalkan dan stok produk tambahan dikembalikan.'
                : 'Reservasi tidak dapat dibatalkan pada status saat ini.',
        );
    }
}
