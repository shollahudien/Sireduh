<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProofController extends Controller
{
    /**
     * Stream the payment proof image for admin review.
     * The file lives on the local (private) disk, so it cannot be
     * reached through /storage; this route is behind auth + role:admin.
     */
    public function show(Reservation $reservation): BinaryFileResponse
    {
        $payment = $reservation->payment;

        abort_if($payment === null || ! $payment->proof_image, 404, 'Bukti pembayaran tidak ditemukan.');

        $path = Storage::disk('private')->path($payment->proof_image);

        abort_if(! file_exists($path), 404, 'File bukti pembayaran tidak ditemukan.');

        AuditLogger::log(
            'payment.proof_viewed',
            'Bukti pembayaran dilihat: '.$reservation->order_code,
            $reservation,
            ['reservation_id' => $reservation->id, 'order_code' => $reservation->order_code, 'payment_id' => $payment->id],
            request(),
            ['file_name' => basename($payment->proof_image), 'file_path' => $payment->proof_image],
            $reservation->order_code
        );

        return response()->file($path);
    }
}
