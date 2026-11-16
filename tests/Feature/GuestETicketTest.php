<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestETicketTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservation(string $status = 'paid', string $orderCode = 'SIRES-QRTEST01'): Reservation
    {
        $visitor = Visitor::create([
            'name' => 'Budi Wisata',
            'whatsapp_number' => '628123456789',
        ]);

        $unit = Unit::create([
            'name' => 'Tenda Keluarga',
            'type' => 'glamping',
            'price' => 500000,
            'capacity' => 4,
            'stock' => 3,
        ]);

        return Reservation::create([
            'order_code' => $orderCode,
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(4)->toDateString(),
            'total_price' => 500000,
            'status' => $status,
        ]);
    }

    public function test_qr_served_as_png_for_valid_ticket_hash(): void
    {
        $reservation = $this->makeReservation();
        $ticket = $reservation->ensureTicket();

        $response = $this->get('/e-ticket/'.$ticket->unique_hash_sha256.'/qr.png');

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertTrue(str_starts_with((string) $response->getContent(), "\x89PNG"));
    }

    public function test_qr_download_forces_attachment_disposition(): void
    {
        $reservation = $this->makeReservation();
        $ticket = $reservation->ensureTicket();

        $response = $this->get('/e-ticket/'.$ticket->unique_hash_sha256.'/qr.png?download=1');

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_qr_returns_404_for_unknown_or_malformed_hash(): void
    {
        $this->get('/e-ticket/'.str_repeat('a', 64).'/qr.png')->assertNotFound();
        $this->get('/e-ticket/short-hash/qr.png')->assertNotFound();
        $this->get('/e-ticket/'.str_repeat('A', 64).'/qr.png')->assertNotFound(); // huruf besar tidak valid
    }

    public function test_paid_reservation_always_gets_ticket_from_guest_status(): void
    {
        $reservation = $this->makeReservation(); // status paid, belum punya tiket

        $response = $this->withoutMiddleware()->postJson('/status', ['order_code' => $reservation->order_code]);

        $response->assertOk();
        $ticket = $response->json('ticket');
        $this->assertNotNull($ticket);
        $this->assertSame(64, strlen($ticket['unique_hash_sha256']));
        $this->assertFalse($ticket['is_used']);
        $this->assertSame(1, Ticket::where('reservation_id', $reservation->id)->count());
    }

    public function test_pending_reservation_has_no_ticket_yet(): void
    {
        $reservation = $this->makeReservation(status: 'pending');

        $response = $this->withoutMiddleware()->postJson('/status', ['order_code' => $reservation->order_code]);

        $response->assertOk();
        $this->assertNull($response->json('ticket'));
        $this->assertSame(0, Ticket::count());
    }

    public function test_ensure_ticket_is_idempotent(): void
    {
        $reservation = $this->makeReservation();
        $first = $reservation->ensureTicket();
        $second = $reservation->ensureTicket();

        $this->assertSame($first->unique_hash_sha256, $second->unique_hash_sha256);
        $this->assertSame(1, Ticket::where('reservation_id', $reservation->id)->count());
    }
}
