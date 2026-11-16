<?php

namespace App\Services;

use App\Jobs\SendETicketJob;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransService
{
    public function enabled(): bool
    {
        if (config('services.midtrans.enabled') === false) {
            return false;
        }

        return filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key'));
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production');
    }

    public function snapJsUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    public function snapApiUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public function createSnapTransaction(Reservation $reservation): Payment
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Payment gateway belum dikonfigurasi.');
        }

        $reservation->loadMissing(['visitor', 'unit', 'payment']);

        $existing = $reservation->payment;
        if ($existing && $existing->provider === 'midtrans' && $existing->status === 'pending' && $existing->snap_token && ($existing->expires_at === null || $existing->expires_at->isFuture())) {
            return $existing;
        }

        $orderId = $reservation->order_code.'-'.now()->format('His');
        $grossAmount = (int) round((float) $reservation->total_price);

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => mb_substr($reservation->visitor?->name ?? 'Tamu', 0, 50),
                'email' => $reservation->visitor?->email,
                'phone' => $reservation->visitor?->whatsapp_number,
            ],
            'item_details' => [[
                'id' => (string) ($reservation->id),
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => mb_substr($reservation->unit?->name ?? 'Reservasi '.$reservation->order_code, 0, 50),
            ]],
            'enabled_payments' => config('services.midtrans.enabled_payments', ['qris', 'gopay', 'shopeepay', 'other_qris', 'bca_va', 'bni_va', 'bri_va', 'permata_va']),
            'callbacks' => [
                'finish' => url('/pembayaran/'.$reservation->order_code),
            ],
        ];

        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->post($this->snapApiUrl(), $payload);

        if ($response->failed()) {
            Log::warning('Midtrans Snap gagal', ['reservation_id' => $reservation->id, 'body' => $response->json() ?? $response->body()]);
            throw new RuntimeException('Gagal membuat pembayaran otomatis. Silakan coba lagi atau gunakan transfer manual.');
        }

        $data = $response->json();

        $payment = DB::transaction(function () use ($reservation, $orderId, $data): Payment {
            return Payment::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'method' => 'gateway',
                    'provider' => 'midtrans',
                    'provider_order_id' => $orderId,
                    'provider_transaction_id' => $data['transaction_id'] ?? null,
                    'payment_type' => null,
                    'snap_token' => $data['token'] ?? null,
                    'snap_redirect_url' => $data['redirect_url'] ?? null,
                    'va_number' => null,
                    'qr_string' => null,
                    'status' => 'pending',
                    'proof_image' => null,
                    'paid_at' => null,
                    'verified_at' => null,
                    'expires_at' => now()->addHours(24),
                    'gateway_payload' => $data,
                ]
            );
        });

        AuditLogger::log(
            'payment.gateway_created',
            'Transaksi Midtrans dibuat untuk '.$reservation->order_code,
            $reservation,
            [
                'order_code' => $reservation->order_code,
                'payment_id' => $payment->id,
                'provider' => 'midtrans',
                'provider_order_id' => $orderId,
                'gross_amount' => $grossAmount,
                'status' => $payment->status,
            ],
            request(),
            null,
            $reservation->order_code
        );

        return $payment;
    }

    public function verifySignature(array $payload): bool
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');
        $signature = (string) ($payload['signature_key'] ?? '');

        return hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey) === $signature;
    }

    public function handleNotification(array $payload): ?Payment
    {
        $providerOrderId = (string) ($payload['order_id'] ?? '');
        Log::info('Midtrans webhook diterima', [
            'order_id' => $providerOrderId,
            'transaction_status' => $payload['transaction_status'] ?? null,
            'payment_type' => $payload['payment_type'] ?? null,
            'fraud_status' => $payload['fraud_status'] ?? null,
        ]);

        if (! $this->verifySignature($payload)) {
            Log::warning('Midtrans signature tidak valid', ['order_id' => $providerOrderId, 'signature_key' => $payload['signature_key'] ?? null]);

            return null;
        }

        $payment = Payment::query()->where('provider_order_id', $providerOrderId)->with('reservation')->first();

        if (! $payment) {
            $orderCode = preg_replace('/-\d{6}$/', '', $providerOrderId);
            $reservation = Reservation::query()->where('order_code', $orderCode)->first();
            $payment = $reservation?->payment;
        }

        if (! $payment) {
            Log::warning('Midtrans payment tidak ditemukan', ['order_id' => $providerOrderId]);

            return null;
        }

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        $paid = $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && $fraudStatus === 'accept');
        $pending = in_array($transactionStatus, ['pending', 'capture'], true) && ! $paid;
        $failed = in_array($transactionStatus, ['deny', 'cancel', 'failure'], true);
        $expired = $transactionStatus === 'expire';

        return DB::transaction(function () use ($payment, $payload, $providerOrderId, $transactionStatus, $paid, $pending, $failed, $expired): Payment {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->with('reservation')->firstOrFail();
            $reservation = Reservation::query()->whereKey($payment->reservation_id)->lockForUpdate()->firstOrFail();

            $updates = [
                'provider_transaction_id' => $payload['transaction_id'] ?? $payment->provider_transaction_id,
                'payment_type' => $payload['payment_type'] ?? $payment->payment_type,
                'gateway_payload' => $payload,
            ];

            $va = data_get($payload, 'va_numbers.0.va_number') ?? data_get($payload, 'permata_va_number');
            if ($va) {
                $updates['va_number'] = $va;
            }
            $qr = data_get($payload, 'qr_string') ?? data_get($payload, 'actions.0.url');
            if ($qr) {
                $updates['qr_string'] = $qr;
            }

            $auditEvent = null;
            $auditDesc = null;

            if ($paid) {
                if ($payment->status === 'verified' && $reservation->status === 'paid') {
                    $payment->update($updates);
                    Log::info('Midtrans notifikasi duplikat/idempotent diabaikan', [
                        'order_id' => $providerOrderId,
                        'payment_id' => $payment->id,
                        'transaction_status' => $transactionStatus,
                    ]);

                    return $payment->fresh(['reservation']);
                }

                $updates['status'] = 'verified';
                $updates['paid_at'] = $payment->paid_at ?? now();
                $updates['verified_at'] = $payment->verified_at ?? now();
                $payment->update($updates);

                if (in_array($reservation->status, ['pending', 'waiting_verification'], true)) {
                    $reservation->update(['status' => 'paid']);
                }
                $reservation->ensureTicket();
                SendETicketJob::dispatchSync($reservation->id);

                Log::info('Pembayaran Midtrans terkonfirmasi otomatis', [
                    'order_id' => $providerOrderId,
                    'payment_id' => $payment->id,
                    'reservation_id' => $reservation->id,
                    'transaction_status' => $transactionStatus,
                    'payment_type' => $payment->payment_type,
                ]);
                $auditEvent = 'payment.gateway_paid';
                $auditDesc = 'Pembayaran Midtrans terkonfirmasi otomatis: '.$reservation->order_code;
            } elseif ($expired) {
                $payment->update(array_merge($updates, ['status' => 'expired']));
                Log::warning('Pembayaran Midtrans kedaluwarsa', ['order_id' => $providerOrderId, 'payment_id' => $payment->id, 'reservation_id' => $reservation->id]);
                $auditEvent = 'payment.gateway_expired';
                $auditDesc = 'Pembayaran Midtrans kedaluwarsa: '.$reservation->order_code;
            } elseif ($failed) {
                $payment->update(array_merge($updates, ['status' => 'failed']));
                Log::warning('Pembayaran Midtrans gagal/ditolak', ['order_id' => $providerOrderId, 'payment_id' => $payment->id, 'reservation_id' => $reservation->id, 'transaction_status' => $transactionStatus]);
                $auditEvent = 'payment.gateway_failed';
                $auditDesc = 'Pembayaran Midtrans gagal: '.$reservation->order_code;
            } elseif ($pending) {
                $payment->update(array_merge($updates, ['status' => 'pending']));
                Log::info('Pembayaran Midtrans pending', ['order_id' => $providerOrderId, 'payment_id' => $payment->id, 'transaction_status' => $transactionStatus]);
                $auditEvent = 'payment.gateway_pending';
                $auditDesc = 'Pembayaran Midtrans pending: '.$reservation->order_code;
            } else {
                $payment->update($updates);
                Log::info('Pembayaran Midtrans status lain', ['order_id' => $providerOrderId, 'payment_id' => $payment->id, 'transaction_status' => $transactionStatus]);
                $auditEvent = 'payment.gateway_notification';
                $auditDesc = 'Notifikasi Midtrans ('.$transactionStatus.'): '.$reservation->order_code;
            }

            if ($auditEvent) {
                AuditLogger::log(
                    $auditEvent,
                    $auditDesc,
                    $reservation,
                    [
                        'order_code' => $reservation->order_code,
                        'reservation_id' => $reservation->id,
                        'payment_id' => $payment->id,
                        'provider' => 'midtrans',
                        'provider_order_id' => $providerOrderId,
                        'transaction_status' => $transactionStatus,
                        'payment_type' => $payload['payment_type'] ?? null,
                        'payment_status' => $payment->fresh()?->status ?? $payment->status,
                        'reservation_status' => $reservation->fresh()?->status ?? $reservation->status,
                    ],
                    request(),
                    null,
                    $reservation->order_code
                );
            }

            return $payment->fresh(['reservation']);
        });
    }
}
