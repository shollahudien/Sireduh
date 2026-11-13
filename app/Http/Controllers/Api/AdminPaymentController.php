<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendETicketJob;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function verify(int $id): JsonResponse
    {
        $reservation = DB::transaction(function () use ($id): Reservation {
            $reservation = Reservation::query()
                ->with('payment')
                ->lockForUpdate()
                ->findOrFail($id);

            $payment = Payment::query()
                ->where('reservation_id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($payment->status, ['pending', 'waiting_verification'], true)) {
                abort(409, 'Pembayaran sudah diproses sebelumnya.');
            }

            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $reservation->update(['status' => 'paid']);

            $reservation->ensureTicket();

            return $reservation->fresh(['payment', 'ticket']);
        });

        SendETicketJob::dispatch($reservation->id);

        return response()->json([
            'success' => true,
            'reservation' => $reservation,
            'message' => 'Pembayaran berhasil diverifikasi dan e-ticket sedang dikirim.',
        ]);
    }
}
