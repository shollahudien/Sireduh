<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'order_code' => ['required', 'string', 'max:64'],
        ]);

        $reservation = Reservation::where('order_code', $request->order_code)
            ->with(['visitor', 'payment', 'unit'])
            ->first();

        if (! $reservation) {
            return response()->json([
                'message' => 'Order code tidak ditemukan.',
            ], 404);
        }

        $reservation->loadMissing('ticket');

        // Jaminan: reservasi lunas selalu punya e-ticket QR meskipun dibuat sebelum fitur ini ada.
        if ($reservation->status === 'paid' && $reservation->ticket === null) {
            $reservation->setRelation('ticket', $reservation->ensureTicket());
        }

        return response()->json([
            'reservation' => $reservation,
            'payment' => $reservation->payment,
            'ticket' => $reservation->ticket ? [
                'unique_hash_sha256' => $reservation->ticket->unique_hash_sha256,
                'is_used' => $reservation->ticket->is_used,
                'used_at' => $reservation->ticket->used_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
