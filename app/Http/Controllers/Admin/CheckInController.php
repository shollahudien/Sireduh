<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;

class CheckInController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['reservation.visitor', 'reservation.unit'])
            ->where('is_used', false)
            ->latest()
            ->paginate(20);

        return view('admin.checkin.index', compact('tickets'));
    }

    public function verify(Ticket $ticket)
    {
        $ticket = Ticket::lockForUpdate()->find($ticket->id);

        if ($ticket->is_used) {
            return back()->with('error', 'QR code sudah digunakan (ticket invalid).');
        }

        $ticket->is_used = true;
        $ticket->used_at = now();
        $ticket->save();

        return back()->with('success', 'Check-in berhasil! Terima kasih sudah datang.');
    }
}
