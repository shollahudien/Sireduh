<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendETicketJob;
use App\Models\Reservation;
use App\Services\ReservationCancellationService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(protected TelegramBotService $telegram) {}

    public function handle(Request $request): JsonResponse
    {
        // Verify secret token if configured
        $secret = config('services.telegram.webhook_secret');

        if (filled($secret)) {
            $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');

            if (! hash_equals($secret, (string) $provided)) {
                return response()->json(['ok' => false], 403);
            }
        }

        $update = $request->all();

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleCallback(array $callback): void
    {
        $data = (string) ($callback['data'] ?? '');
        $callbackId = (string) ($callback['id'] ?? '');
        $message = $callback['message'] ?? [];
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $messageId = (int) ($message['message_id'] ?? 0);

        if (! preg_match('/^(v|r):(\d+)$/', $data, $m)) {
            $this->telegram->answerCallbackQuery($callbackId, 'Aksi tidak dikenali.');

            return;
        }

        $action = $m[1];
        $reservationId = (int) $m[2];

        $reservation = Reservation::query()
            ->with(['visitor', 'unit', 'payment', 'ticket'])
            ->find($reservationId);

        if ($reservation === null) {
            $this->telegram->answerCallbackQuery($callbackId, 'Reservasi tidak ditemukan.');

            return;
        }

        if ($action === 'v') {
            $this->verify($reservation, $callbackId, $chatId, $messageId);
        } else {
            $this->reject($reservation, $callbackId, $chatId, $messageId);
        }
    }

    protected function verify(Reservation $reservation, string $callbackId, int $chatId, int $messageId): void
    {
        try {
            $payment = $reservation->payment;

            if ($payment === null || ! in_array($payment->status, ['pending', 'waiting_verification'], true)) {
                $this->telegram->answerCallbackQuery($callbackId, 'Pembayaran sudah diproses.');

                return;
            }

            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
            ]);

            $reservation->update(['status' => 'paid']);
            $reservation->ensureTicket();

            SendETicketJob::dispatchSync($reservation->id);

            $this->telegram->answerCallbackQuery($callbackId, 'Pembayaran diverifikasi ✅');

            $text = "✅ <b>Diverifikasi</b>\n\nOrder <code>{$reservation->order_code}</code> telah diverifikasi.\nE-ticket sedang dikirim ke WhatsApp tamu.";

            if ($messageId > 0 && $chatId > 0) {
                $this->telegram->editMessageCaption($chatId, $messageId, $text, ['inline_keyboard' => []]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram verify failed', ['reservation_id' => $reservation->id, 'error' => $e->getMessage()]);
            $this->telegram->answerCallbackQuery($callbackId, 'Gagal verifikasi. Cek log.');
        }
    }

    protected function reject(Reservation $reservation, string $callbackId, int $chatId, int $messageId): void
    {
        $cancellations = app(ReservationCancellationService::class);

        $cancelled = $cancellations->cancel($reservation);

        if ($cancelled) {
            $this->telegram->answerCallbackQuery($callbackId, 'Pesanan ditolak ❌');

            $text = "❌ <b>Ditolak</b>\n\nOrder <code>{$reservation->order_code}</code> dibatalkan.\nStok produk tambahan dikembalikan.";

            if ($messageId > 0 && $chatId > 0) {
                $this->telegram->editMessageCaption($chatId, $messageId, $text, ['inline_keyboard' => []]);
            }
        } else {
            $this->telegram->answerCallbackQuery($callbackId, 'Tidak dapat dibatalkan pada status ini.');
        }
    }
}
