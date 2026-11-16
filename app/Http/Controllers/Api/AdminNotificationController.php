<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    /**
     * Pending payments for the admin navbar bell.
     * Polled every 10 seconds by layouts/admin.blade.php.
     *
     * Returns: total pending count + latest 5 pending payments.
     */
    public function index(): JsonResponse
    {
        $latest = Payment::query()
            ->where('status', 'pending')
            ->whereHas('reservation', fn ($q) => $q->whereIn('status', ['pending', 'waiting_verification']))
            ->with(['reservation', 'reservation.visitor', 'reservation.unit'])
            ->latest()
            ->take(5)
            ->get();

        $countQuery = Payment::query()
            ->where('status', 'pending')
            ->whereHas('reservation', fn ($q) => $q->whereIn('status', ['pending', 'waiting_verification']));

        return response()->json([
            'count' => $countQuery->count(),
            'total_pending' => $countQuery->count(),
            'has_more' => $countQuery->count() > 5,
            'latest' => $latest->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'reservation_id' => $payment->reservation?->id,
                'order_code' => $payment->reservation?->order_code,
                'visitor_name' => $payment->reservation?->visitor?->name,
                'visitor_whatsapp' => $payment->reservation?->visitor?->whatsapp_number,
                'unit_name' => $payment->reservation?->unit?->name,
                'total_amount' => (float) $payment->reservation?->total_price,
                'proof_image' => $payment->proof_image,
                'created_at' => $payment->created_at?->toIso8601String(),
                'created_at_human' => $payment->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }
}
