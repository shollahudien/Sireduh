<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendETicketJob;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with([
            'reservation.visitor',
            'reservation.unit',
        ])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Verifikasi pembayaran berdasarkan reservation_id
     * (konsisten dengan route PUT /admin/payments/{reservation})
     */
    public function verifyByReservation(Reservation $reservation)
    {
        DB::transaction(function () use ($reservation) {
            $payment = Payment::where('reservation_id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'verified') {
                return;
            }

            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $reservation->update(['status' => 'paid']);

            // Buat tiket jika belum ada
            if (! $reservation->ticket) {
                $reservation->ticket()->create([
                    'unique_hash_sha256' => hash('sha256', $reservation->order_code.now()->timestamp),
                    'is_used' => false,
                ]);
            }

            SendETicketJob::dispatch($reservation->id);
        });

        return back()->with('success', 'Pembayaran diverifikasi. E-ticket dikirim ke WhatsApp tamu.');
    }
}
