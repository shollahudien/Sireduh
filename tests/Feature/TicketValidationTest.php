<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_ticket_reservation_without_unit_succeeds(): void
    {
        $tiketMasuk = Product::create([
            'name' => 'Tiket Masuk',
            'category' => 'tiket_masuk',
            'price' => 15000.00,
            'type' => 'addon',
            'stock' => 500,
        ]);

        $response = $this->postJson('/api/reservations', [
            'visitor_name' => 'Tamu Mandiri',
            'visitor_whatsapp' => '085555555555',
            'unit_id' => null,
            'check_in_date' => now()->addDays(1)->toDateString(),
            'total_price' => 15000.00,
            'addons' => [
                ['product_id' => $tiketMasuk->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('reservations', [
            'unit_id' => null,
        ]);
    }

    public function test_qr_code_one_time_validation(): void
    {
        $unit = Unit::create([
            'name' => 'Glamping 3',
            'type' => 'glamping',
            'price' => 300000.00,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $visitor = Visitor::create([
            'name' => 'Joko',
            'whatsapp_number' => '085544332211',
        ]);

        $reservation = Reservation::create([
            'order_code' => 'SIRES-TICKET99',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => '2026-12-01',
            'check_out_date' => '2026-12-02',
            'total_price' => 300000.00,
            'status' => 'confirmed',
        ]);

        $hash = hash('sha256', 'SIRES-TICKET99');

        Ticket::create([
            'reservation_id' => $reservation->id,
            'unique_hash_sha256' => $hash,
            'is_used' => false,
        ]);

        $officer = User::create([
            'name' => 'Petugas Checkin',
            'email' => 'checkin@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'checkin',
        ]);

        $this->actingAs($officer);

        // Scan pertama → sukses
        $first = $this->postJson('/api/checkin/validate', ['hash' => $hash]);
        $first->assertStatus(200)->assertJsonPath('success', true);

        // Scan kedua → ditolak (tiket sudah dipakai)
        $second = $this->postJson('/api/checkin/validate', ['hash' => $hash]);
        $second->assertStatus(409)->assertJsonPath('success', false);
    }

    public function test_fake_qr_code_is_rejected(): void
    {
        $officer = User::create([
            'name' => 'Petugas Checkin',
            'email' => 'checkin2@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'checkin',
        ]);

        $this->actingAs($officer);

        $fakeHash = str_repeat('f', 64);

        $response = $this->postJson('/api/checkin/validate', ['hash' => $fakeHash]);
        $response->assertStatus(404)->assertJsonPath('success', false);
    }
}
