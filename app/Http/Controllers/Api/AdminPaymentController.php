<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendETicketJob;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function verify(Request $request, int $id): JsonResponse
    {
        $result = DB::transaction(function () use ($id): array {
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

            $before = [
                'payment_status' => $payment->status,
                'reservation_status' => $reservation->status,
            ];

            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $reservation->update(['status' => 'paid']);

            $reservation->ensureTicket();

            $reservation = $reservation->fresh(['payment', 'ticket']);

            return ['reservation' => $reservation, 'before' => $before];
        });

        /** @var Reservation $reservation */
        $reservation = $result['reservation'];

        SendETicketJob::dispatchSync($reservation->id);

        \Log::info('Pembayaran diverifikasi admin', [
            'reservation_id' => $reservation->id,
            'order_code' => $reservation->order_code,
            'payment_status' => $reservation->payment?->status,
            'reservation_status' => $reservation->status,
        ]);

        AuditLogger::log(
            'payment.verified',
            'Pembayaran diverifikasi admin untuk '.$reservation->order_code,
            $reservation,
            [
                'order_code' => $reservation->order_code,
                'payment_id' => $reservation->payment?->id,
                'from' => $result['before'],
                'to' => [
                    'payment_status' => $reservation->payment?->status,
                    'reservation_status' => $reservation->status,
                ],
            ],
            $request,
            null,
            $reservation->order_code
        );

        return response()->json([
            'success' => true,
            'reservation' => $reservation,
            'message' => 'Pembayaran berhasil diverifikasi dan e-ticket sedang dikirim.',
        ]);
    }
}
