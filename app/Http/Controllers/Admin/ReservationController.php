<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendETicketJob;
use App\Models\Reservation;
use App\Models\Unit;

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
        SendETicketJob::dispatch($reservation->id);

        return back()->with('success', 'Reservasi diverifikasi dan E-ticket WhatsApp terkirim.');
    }

    public function cancel(Reservation $reservation)
    {
        $reservation->status = 'cancelled';
        $reservation->save();

        // Return unit stock
        if ($reservation->unit) {
            $reservation->unit->increment('stock', 1);
        }

        return back()->with('success', 'Reservasi dibatalkan dan stok dikembalikan.');
    }
}
