<?php

namespace Tests\Feature;

use App\Jobs\NotifyTelegramVerificationJob;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\Visitor;
use App\Services\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function seedReservation(string $status = 'waiting_verification'): Reservation
    {
        $visitor = Visitor::create([
            'name' => 'Tamu Telegram',
            'whatsapp_number' => '081700000099',
        ]);

        $unit = Unit::create([
            'name' => 'Tenda TG',
            'type' => 'glamping',
            'price' => 300000,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $reservation = Reservation::create([
            'order_code' => 'SIRES-TGTEST',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => '2027-03-01',
            'check_out_date' => '2027-03-02',
            'total_price' => 300000,
            'status' => $status,
        ]);

        Payment::create([
            'reservation_id' => $reservation->id,
            'proof_image' => 'reservations/proofs/'.$reservation->id.'/bukti.png',
            'status' => 'pending',
        ]);

        Storage::fake('private');
        Storage::disk('private')->put('reservations/proofs/'.$reservation->id.'/bukti.png', 'fake-image-bytes');

        return $reservation->fresh(['payment']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-bot-token',
            'services.telegram.chat_id' => '12345',
            'services.telegram.webhook_secret' => 'test-secret',
        ]);
    }

    public function test_webhook_verify_callback_marks_reservation_paid(): void
    {
        $reservation = $this->seedReservation('waiting_verification');

        $response = $this->postJson('/api/telegram/webhook', [
            'callback_query' => [
                'id' => 'cb-1',
                'data' => 'v:'.$reservation->id,
                'message' => [
                    'message_id' => 42,
                    'chat' => ['id' => 12345],
                ],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);

        $response->assertOk()->assertJsonPath('ok', true);

        $reservation->refresh();
        $this->assertSame('paid', $reservation->status);
        $this->assertNotNull($reservation->payment->verified_at);
    }

    public function test_webhook_reject_callback_cancels_reservation(): void
    {
        $reservation = $this->seedReservation('waiting_verification');

        $response = $this->postJson('/api/telegram/webhook', [
            'callback_query' => [
                'id' => 'cb-2',
                'data' => 'r:'.$reservation->id,
                'message' => [
                    'message_id' => 43,
                    'chat' => ['id' => 12345],
                ],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);

        $response->assertOk()->assertJsonPath('ok', true);

        $reservation->refresh();
        $this->assertSame('cancelled', $reservation->status);
    }

    public function test_webhook_rejects_invalid_secret(): void
    {
        $this->postJson('/api/telegram/webhook', [
            'callback_query' => [
                'id' => 'cb-3',
                'data' => 'v:1',
                'message' => ['message_id' => 1, 'chat' => ['id' => 1]],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertStatus(403);
    }

    public function test_job_sends_photo_to_telegram(): void
    {
        $reservation = $this->seedReservation('waiting_verification');

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]], 200),
        ]);

        $job = new NotifyTelegramVerificationJob($reservation->id);
        $job->handle(app(TelegramBotService::class));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendPhoto')
                && str_contains($request->url(), 'test-bot-token');
        });
    }

    public function test_payment_upload_dispatches_telegram_job(): void
    {
        Queue::fake();

        Storage::fake('private');
        $reservation = $this->seedPendingReservationForUpload();

        $this->post('/api/payments/upload', [
            'order_code' => $reservation->order_code,
            'proof_image' => UploadedFile::fake()->image('bukti.png'),
        ])->assertStatus(201);

        Queue::assertPushed(NotifyTelegramVerificationJob::class);
    }

    private function seedPendingReservationForUpload(): Reservation
    {
        $visitor = Visitor::create([
            'name' => 'Upload Tester',
            'whatsapp_number' => '081700000002',
        ]);

        $unit = Unit::create([
            'name' => 'Tenda Upload',
            'type' => 'glamping',
            'price' => 300000,
            'capacity' => 2,
            'stock' => 1,
        ]);

        return Reservation::create([
            'order_code' => 'SIRES-TGUPLOAD',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => '2027-04-01',
            'check_out_date' => '2027-04-02',
            'total_price' => 300000,
            'status' => 'pending',
        ]);
    }
}
