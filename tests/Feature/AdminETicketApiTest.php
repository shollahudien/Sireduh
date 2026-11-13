<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminETicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin E-tiket',
            'email' => 'admin-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    private function paidReservation(): Reservation
    {
        $visitor = Visitor::create([
            'name' => 'E-tiket Test',
            'whatsapp_number' => '081234567890',
            'email' => 'tiket@example.com',
        ]);

        $unit = Unit::create([
            'name' => 'Tenda E-tiket Test',
            'type' => 'glamping',
            'price' => 300000,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $reservation = Reservation::create([
            'order_code' => 'SIRES-ETICKET1',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => '2027-03-01',
            'check_out_date' => '2027-03-02',
            'total_price' => 300000,
            'status' => 'paid',
        ]);

        Ticket::create([
            'reservation_id' => $reservation->id,
            'unique_hash_sha256' => str_repeat('ab', 32),
            'is_used' => false,
        ]);

        return $reservation;
    }

    public function test_admin_can_manually_send_eticket_whatsapp(): void
    {
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        config(['services.fonnte.token' => 'token-uji-coba']);

        $this->actingAs($this->admin());

        $reservation = $this->paidReservation();

        $response = $this->postJson("/api/admin/eticket/send/{$reservation->id}");

        $response->assertOk()->assertJsonPath('success', true);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && str_contains($request->body(), '6281234567890')
            && str_contains($request->body(), 'SIRES-ETICKET1')
            && str_contains($request->body(), 'name="file"'));
    }

    public function test_eticket_cannot_be_sent_for_unpaid_reservation(): void
    {
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        config(['services.fonnte.token' => 'token-uji-coba']);

        $this->actingAs($this->admin());

        $reservation = $this->paidReservation();
        $reservation->update(['status' => 'pending']);

        $response = $this->postJson("/api/admin/eticket/send/{$reservation->id}");

        $response->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_eticket_returns_error_when_fonnte_token_missing(): void
    {
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        config(['services.fonnte.token' => '']);

        $this->actingAs($this->admin());

        $reservation = $this->paidReservation();

        $response = $this->postJson("/api/admin/eticket/send/{$reservation->id}");

        $response->assertStatus(422)->assertJsonPath('success', false);

        Http::assertNothingSent();
    }
}
