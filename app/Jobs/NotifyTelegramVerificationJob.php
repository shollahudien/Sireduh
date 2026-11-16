<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NotifyTelegramVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $reservationId;

    public function __construct(int $reservationId)
    {
        $this->reservationId = $reservationId;
    }

    public function handle(TelegramBotService $telegram): void
    {
        if (! $telegram->isConfigured()) {
            return;
        }

        $reservation = Reservation::query()
            ->with(['visitor', 'unit', 'payment', 'addons.product'])
            ->find($this->reservationId);

        if ($reservation === null) {
            return;
        }

        $payment = $reservation->payment;

        if ($payment === null || blank($payment->proof_image)) {
            return;
        }

        $caption = $this->buildCaption($reservation);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Verifikasi', 'callback_data' => 'v:'.$reservation->id],
                    ['text' => '❌ Tolak', 'callback_data' => 'r:'.$reservation->id],
                ],
            ],
        ];

        try {
            $photoBytes = Storage::disk('private')->get($payment->proof_image);

            $result = $telegram->sendPhoto($photoBytes, 'bukti-pembayaran.jpg', $caption, $keyboard);

            if ($result === null) {
                $telegram->sendMessage($caption."\n\n(Bukti tidak bisa dilampirkan)", $keyboard);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram notify failed, falling back to text', ['error' => $e->getMessage()]);

            $telegram->sendMessage($caption."\n\n(Bukti tidak bisa dilampirkan)", $keyboard);
        }
    }

    protected function buildCaption(Reservation $reservation): string
    {
        $visitor = $reservation->visitor;
        $addons = $reservation->addons->map(fn ($a) => ($a->product?->name ?? 'Produk').' x'.$a->quantity)->implode(', ');

        $lines = [];
        $lines[] = '🔔 <b>Verifikasi Pembayaran Baru</b>';
        $lines[] = '';
        $lines[] = '📋 Order: <code>'.$reservation->order_code.'</code>';
        $lines[] = '👤 Nama: '.e($visitor?->name ?? '-');
        $lines[] = '📱 WA: '.e($visitor?->whatsapp_number ?? '-');
        $lines[] = '🏷️ Unit: '.e($reservation->unit?->name ?? 'Tiket Masuk');
        $lines[] = '📅 Check-in: '.$reservation->check_in_date?->format('d M Y');
        $lines[] = '📅 Check-out: '.$reservation->check_out_date?->format('d M Y');
        $lines[] = '💰 Total: Rp '.number_format((float) $reservation->total_price, 0, ',', '.');
        if ($addons !== '') {
            $lines[] = '📦 Add-ons: '.e($addons);
        }
        $lines[] = '';
        $lines[] = 'Silakan verifikasi atau tolak pembayaran ini.';

        return implode("\n", $lines);
    }

    public function tags(): array
    {
        return ['telegram', 'reservation-'.$this->reservationId];
    }
}
