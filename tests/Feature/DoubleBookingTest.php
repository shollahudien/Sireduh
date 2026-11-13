<?php

namespace Tests\Feature;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoubleBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_reservation_succeeds_and_second_conflicts(): void
    {
        $unit = Unit::create([
            'name' => 'Glamping Date Lock',
            'type' => 'glamping',
            'price' => 500000.00,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $first = $this->postJson('/api/reservations', [
            'visitor_name' => 'Budi Santoso',
            'visitor_whatsapp' => '081234567890',
            'visitor_email' => 'budi@example.com',
            'unit_id' => $unit->id,
            'check_in_date' => '2026-12-01',
            'check_out_date' => '2026-12-03',
            'total_price' => 1000000.00,
        ]);

        $first->assertStatus(201)->assertJsonPath('success', true);

        $second = $this->postJson('/api/reservations', [
            'visitor_name' => 'Ani Wijaya',
            'visitor_whatsapp' => '089876543210',
            'visitor_email' => 'ani@example.com',
            'unit_id' => $unit->id,
            'check_in_date' => '2026-12-01',
            'check_out_date' => '2026-12-03',
            'total_price' => 1000000.00,
        ]);

        $second->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_overlapping_partial_dates_are_rejected(): void
    {
        $unit = Unit::create([
            'name' => 'Glamping Overlap',
            'type' => 'glamping',
            'price' => 500000.00,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $this->postJson('/api/reservations', [
            'visitor_name' => 'First',
            'visitor_whatsapp' => '081111111111',
            'unit_id' => $unit->id,
            'check_in_date' => '2026-12-01',
            'check_out_date' => '2026-12-05',
            'total_price' => 2000000.00,
        ])->assertStatus(201);

        $this->postJson('/api/reservations', [
            'visitor_name' => 'Second',
            'visitor_whatsapp' => '082222222222',
            'unit_id' => $unit->id,
            'check_in_date' => '2026-12-03',
            'check_out_date' => '2026-12-07',
            'total_price' => 2000000.00,
        ])->assertStatus(409);
    }

    public function test_booking_past_date_is_rejected(): void
    {
        $unit = Unit::create([
            'name' => 'Glamping Past',
            'type' => 'glamping',
            'price' => 500000.00,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $response = $this->postJson('/api/reservations', [
            'visitor_name' => 'Past Booker',
            'visitor_whatsapp' => '083333333333',
            'unit_id' => $unit->id,
            'check_in_date' => now()->subDays(5)->toDateString(),
            'check_out_date' => now()->subDays(3)->toDateString(),
            'total_price' => 1000000.00,
        ]);

        $response->assertStatus(422);
    }
}
