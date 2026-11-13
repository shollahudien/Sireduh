<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentUploadController extends Controller
{
    public function show(string $orderCode): JsonResponse
    {
        $reservation = Reservation::query()
            ->with(['visitor', 'unit', 'payment', 'addons.product'])
            ->where('order_code', $orderCode)
            ->first();

        if ($reservation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'reservation' => [
                'id' => $reservation->id,
                'order_code' => $reservation->order_code,
                'visitor_name' => $reservation->visitor?->name,
                'visitor_whatsapp' => $reservation->visitor?->whatsapp_number,
                'unit_name' => $reservation->unit?->name,
                'check_in_date' => $reservation->check_in_date?->toDateString(),
                'check_out_date' => $reservation->check_out_date?->toDateString(),
                'total_price' => (float) $reservation->total_price,
                'status' => $reservation->status,
                'addons' => $reservation->addons->map(fn ($addon) => [
                    'product_name' => $addon->product?->name,
                    'quantity' => $addon->quantity,
                    'price' => (float) $addon->price,
                ])->values(),
            ],
            'payment' => $reservation->payment ? [
                'status' => $reservation->payment->status,
                'verified_at' => $reservation->payment->verified_at?->toIso8601String(),
                'uploaded_at' => $reservation->payment->created_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'proof_image' => ['required', 'image', 'max:2048'],
        ]);

        $reservation = Reservation::findOrFail($validated['reservation_id']);

        if (! in_array($reservation->status, ['pending', 'waiting_verification'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak dapat menerima bukti pembayaran pada status saat ini.',
            ], 409);
        }

        $path = $request->file('proof_image')->store(
            'reservations/proofs/'.$reservation->id,
            'private'
        );

        $payment = Payment::updateOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'proof_image' => $path,
                'status' => 'pending',
                'verified_at' => null,
            ]
        );

        $reservation->update(['status' => 'waiting_verification']);

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'status' => $payment->status,
            'message' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi admin.',
        ], 201);
    }
}
