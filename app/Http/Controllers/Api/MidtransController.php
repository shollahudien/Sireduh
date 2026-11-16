<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MidtransController extends Controller
{
    public function __construct(protected MidtransService $midtrans) {}

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'max:20'],
        ]);

        $reservation = Reservation::query()->where('order_code', $validated['order_code'])->first();

        if (! $reservation) {
            return response()->json(['success' => false, 'message' => 'Order tidak ditemukan.'], 404);
        }

        if (! in_array($reservation->status, ['pending', 'waiting_verification'], true)) {
            return response()->json(['success' => false, 'message' => 'Reservasi tidak dapat dibayar pada status saat ini.'], 409);
        }

        try {
            $payment = $this->midtrans->createSnapTransaction($reservation);

            return response()->json([
                'success' => true,
                'snap_token' => $payment->snap_token,
                'redirect_url' => $payment->snap_redirect_url,
                'client_key' => $this->midtrans->clientKey(),
                'snap_js_url' => $this->midtrans->snapJsUrl(),
                'order_code' => $reservation->order_code,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function notification(Request $request): JsonResponse
    {
        $payment = $this->midtrans->handleNotification($request->all());

        return response()->json(['success' => (bool) $payment]);
    }
}
