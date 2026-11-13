<?php

namespace App\Services;

use App\Models\Reservation;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ETicketWhatsappService
{
    /**
     * Generate the SHA-256 QR e-ticket as PNG bytes.
     */
    public function generateQrPng(string $hash): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 6,
            'outputBase64' => false,
            'eccLevel' => 'M',
        ]);

        return (new QRCode($options))->render($hash);
    }

    /**
     * Send the e-ticket (QR image + caption) to the visitor over WhatsApp.
     *
     * @throws RuntimeException When Fonnte token is missing, phone is invalid, or the API call fails.
     */
    public function send(Reservation $reservation): void
    {
        $reservation->loadMissing(['visitor', 'unit', 'addons.product', 'ticket']);

        $ticket = $reservation->ticket;

        if ($ticket === null || ! $ticket->unique_hash_sha256) {
            throw new RuntimeException('E-ticket belum dibuat. Reservasi harus diverifikasi admin terlebih dahulu.');
        }

        $token = config('services.fonnte.token');

        if (blank($token)) {
            throw new RuntimeException('FONNTE_API_TOKEN belum diatur di .env. Kirim e-ticket tidak bisa dilakukan.');
        }

        $phone = $this->normalizePhone($reservation->visitor?->whatsapp_number);

        if ($phone === null) {
            throw new RuntimeException('Nomor WhatsApp tamu tidak valid.');
        }

        $message = $this->buildMessage($reservation);
        $png = $this->generateQrPng($ticket->unique_hash_sha256);

        $response = Http::withHeaders(['token' => $token])
            ->attach('file', $png, 'e-ticket.png')
            ->post(config('services.fonnte.api_url', 'https://api.fonnte.com/send'), [
                'target' => $phone,
                'message' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('Fonnte send failed', [
                'order_code' => $reservation->order_code,
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Fonnte gagal mengirim (HTTP '.$response->status().'). Cek token & nomor WA tujuan.');
        }

        Log::info('E-ticket WhatsApp sent', [
            'order_code' => $reservation->order_code,
            'phone' => $phone,
        ]);
    }

    /**
     * Normalize Indonesian phone numbers to the 62xxxxxxxx format.
     */
    public function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    protected function buildMessage(Reservation $reservation): string
    {
        $visitor = $reservation->visitor;
        $addons = $reservation->addons->pluck('product.name')->implode(', ');

        $message = "🎉 *E-TICKET SIRESTEDUH*\n\n"
            ."📋 Order ID: {$reservation->order_code}\n"
            ."👤 Nama: {$visitor?->name}\n"
            .'🏷️ Unit: '.($reservation->unit?->name ?? 'Tiket Masuk')."\n"
            .'📅 Check-in: '.$reservation->check_in_date?->format('d F Y')."\n"
            .'📅 Check-out: '.$reservation->check_out_date?->format('d F Y')."\n"
            .'💰 Total: Rp '.number_format((float) $reservation->total_price, 0, ',', '.')."\n";

        if ($addons !== '') {
            $message .= "📦 Add-ons: {$addons}\n";
        }

        $message .= "\n🎫 Tunjukkan QR code pada tiket ini saat check-in di pintu masuk.\nSelamat menikmati wisata di BUMDes Wisata Teduh! 🙏";

        return $message;
    }
}
