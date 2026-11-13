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
            ->with(['reservation', 'reservation.visitor', 'reservation.unit'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'count' => Payment::query()->where('status', 'pending')->count(),
            'total_pending' => Payment::query()->where('status', 'pending')->count(),
            'has_more' => Payment::query()->where('status', 'pending')->count() > 5,
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
