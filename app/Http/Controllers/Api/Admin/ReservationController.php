<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    /**
     * Reservations that are awaiting payment verification,
     * with visitor, unit, and payment proof attached.
     */
    public function pending(): JsonResponse
    {
        $reservations = Reservation::query()
            ->with(['visitor', 'unit', 'payment'])
            ->where('status', 'waiting_verification')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'total_pending' => Reservation::query()->where('status', 'waiting_verification')->count(),
            'data' => $reservations->map(fn (Reservation $reservation): array => [
                'id' => $reservation->id,
                'order_code' => $reservation->order_code,
                'visitor_name' => $reservation->visitor?->name,
                'visitor_whatsapp' => $reservation->visitor?->whatsapp_number,
                'unit_name' => $reservation->unit?->name,
                'check_in_date' => optional($reservation->check_in_date)->toDateString(),
                'check_in_time' => $reservation->check_in_time ? substr($reservation->check_in_time, 0, 5) : null,
                'check_out_date' => optional($reservation->check_out_date)->toDateString(),
                'check_out_time' => $reservation->check_out_time ? substr($reservation->check_out_time, 0, 5) : null,
                'total_price' => (float) $reservation->total_price,
                'payment_status' => $reservation->payment?->status,
                'proof_image' => $reservation->payment?->proof_image,
                'payment_method' => $reservation->payment?->method,
                'payment_provider' => $reservation->payment?->provider,
                'provider_order_id' => $reservation->payment?->provider_order_id,
                'provider_transaction_id' => $reservation->payment?->provider_transaction_id,
                'payment_type' => $reservation->payment?->payment_type,
                'created_at' => $reservation->created_at?->toIso8601String(),
                'created_at_human' => $reservation->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }
}
