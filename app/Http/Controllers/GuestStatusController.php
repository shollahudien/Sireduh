<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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

        $payment = $reservation->payment;

        // Bukti pembayaran tersimpan di disk private; hanya bisa diakses lewat URL bertanda tangan.
        $proofUrl = $payment !== null && $payment->proof_image
            ? URL::temporarySignedRoute('storage.local', now()->addHour(), ['path' => $payment->proof_image], absolute: false)
            : null;

        return response()->json([
            'reservation' => $reservation,
            'payment' => $payment,
            'proof_url' => $proofUrl,
            'ticket' => $reservation->ticket ? [
                'unique_hash_sha256' => $reservation->ticket->unique_hash_sha256,
                'is_used' => $reservation->ticket->is_used,
                'used_at' => $reservation->ticket->used_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
