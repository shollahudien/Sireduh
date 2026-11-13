<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ETicketWhatsappService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ETicketSendController extends Controller
{
    /**
     * Manually send (or re-send) the SHA-256 QR e-ticket over WhatsApp.
     * Restricted to reservations that have been verified by admin.
     */
    public function send(Reservation $reservation, ETicketWhatsappService $service): JsonResponse
    {
        if (! in_array($reservation->status, ['paid', 'confirmed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode hanya bisa dikirim untuk reservasi yang sudah diverifikasi (status paid/confirmed).',
            ], 422);
        }

        try {
            $service->send($reservation->loadMissing(['visitor', 'unit', 'addons.product', 'ticket']));
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'E-ticket berhasil dikirim ke WhatsApp tamu.',
        ]);
    }
}
