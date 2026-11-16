<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyTelegramVerificationJob;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\AuditLogger;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentUploadController extends Controller
{
    public function __construct(protected MidtransService $midtrans) {}

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

        $payment = $reservation->payment;

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
            'payment' => $payment ? [
                'status' => $payment->status,
                'method' => $payment->method ?? 'manual_transfer',
                'provider' => $payment->provider,
                'payment_type' => $payment->payment_type,
                'snap_token' => $payment->snap_token,
                'snap_redirect_url' => $payment->snap_redirect_url,
                'va_number' => $payment->va_number,
                'qr_string' => $payment->qr_string,
                'verified_at' => $payment->verified_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'uploaded_at' => $payment->created_at?->toIso8601String(),
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'has_proof' => (bool) $payment->proof_image,
            ] : null,
            'gateway' => [
                'enabled' => $this->midtrans->enabled(),
                'client_key' => $this->midtrans->clientKey(),
                'snap_js_url' => $this->midtrans->snapJsUrl(),
                'methods' => ['QRIS', 'GoPay', 'ShopeePay', 'Virtual Account (BCA/BNI/BRI/Permata)'],
            ],
            'manual' => [
                'bank' => config('services.payment.bank'),
                'account_number' => config('services.payment.account_number'),
                'account_name' => config('services.payment.account_name'),
                'whatsapp' => config('services.payment.whatsapp'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'max:20'],
            'proof_image' => ['required', 'image', 'max:2048'],
        ]);

        $reservation = Reservation::query()
            ->where('order_code', $validated['order_code'])
            ->first();

        if ($reservation === null) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        if (! in_array($reservation->status, ['pending', 'waiting_verification'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak dapat menerima bukti pembayaran pada status saat ini.',
            ], 409);
        }

        $file = $request->file('proof_image');
        $path = $file->store(
            'reservations/proofs/'.$reservation->id,
            'private'
        );

        $oldStatus = $reservation->status;
        $payment = Payment::updateOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'method' => 'manual_transfer',
                'provider' => null,
                'provider_order_id' => null,
                'snap_token' => null,
                'snap_redirect_url' => null,
                'proof_image' => $path,
                'status' => 'pending',
                'verified_at' => null,
                'paid_at' => null,
            ]
        );

        $reservation->update(['status' => 'waiting_verification']);

        NotifyTelegramVerificationJob::dispatchSync($reservation->id);

        \Log::info('Bukti pembayaran diunggah', [
            'reservation_id' => $reservation->id,
            'order_code' => $reservation->order_code,
            'payment_id' => $payment->id,
            'status' => $payment->status,
        ]);

        AuditLogger::log(
            'payment.proof_uploaded',
            'Bukti pembayaran diunggah untuk '.$reservation->order_code,
            $reservation,
            [
                'order_code' => $reservation->order_code,
                'reservation_id' => $reservation->id,
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
                'reservation_status_from' => $oldStatus,
                'reservation_status_to' => 'waiting_verification',
            ],
            $request,
            array_merge(AuditLogger::fileMeta($file), ['file_path' => $path]),
            $reservation->order_code
        );

        return response()->json([
            'success' => true,
            'payment_id' => $payment->id,
            'reservation_id' => $reservation->id,
            'status' => $payment->status,
            'message' => 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi admin.',
        ], 201);
    }
}
