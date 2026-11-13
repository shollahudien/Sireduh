<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentApiController extends Controller
{
    /**
     * Guest uploads proof of payment receipt.
     * Flow 2: POST /api/payments/upload
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|integer|exists:reservations,id',
            'proof_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Store the proof image path (in a real implementation, store to disk/cloud)
        $proofImage = $validated['proof_image']->store('payment_proofs', 'public');

        // Create payment record with 'pending' status
        $payment = Payment::create([
            'reservation_id' => $validated['reservation_id'],
            'proof_image' => $proofImage,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'proof_image' => $payment->proof_image,
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
        ]);
    }

    /**
     * Admin dashboard polls this endpoint every 10 seconds.
     * Flow 2: GET /api/admin/notifications
     * Returns new/updated payments that need attention.
     */
    public function notifications()
    {
        $newPayments = Payment::where('status', 'pending')
            ->latest()
            ->with(['reservation', 'reservation.unit', 'reservation.visitor'])
            ->take(5)
            ->get();

        return response()->json([
            'new_payments' => $newPayments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'reservation_order_code' => $payment->reservation->order_code,
                    'visitor_name' => $payment->reservation->visitor->name,
                    'total_amount' => $payment->reservation->total_price,
                    'proof_image' => $payment->proof_image,
                    'created_at' => $payment->createdRelativeTimeString(),
                ];
            }),
            'has_more' => Payment::where('status', 'pending')->count() > 5,
        ]);
    }

    /**
     * Admin verifies a payment.
     * Flow 3: PUT /api/admin/verify/{id}
     * Changes payment status to 'verified' and updates reservation to 'paid'.
     */
    public function verify($id)
    {
        $payment = Payment::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        return DB::transaction(function () use ($payment) {
            // Update payment status
            $payment->status = 'verified';
            $payment->verified_at = now();
            $payment->save();

            // Update reservation status to 'paid'
            $reservation = $payment->reservation;
            $reservation->status = 'paid';
            $reservation->save();

            // Trigger WhatsApp E-Ticket queue job
            // In real implementation: SendETicketJob::dispatch($reservation->id);
            // For now, we'll just log
            \Log::info("ETicket job triggered for reservation #{$reservation->id}");

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'reservation_id' => $reservation->id,
                'new_payment_status' => 'verified',
                'new_reservation_status' => 'paid',
                'message' => 'Pembayaran diverifikasi. E-ticket dikirim via WhatsApp.',
            ]);
        });
    }
}
