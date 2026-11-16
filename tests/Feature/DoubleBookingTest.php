<?php

namespace Tests\Feature;

use App\Models\GlampingPackage;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DoubleBookingTest extends TestCase
{
    use RefreshDatabase;

    private function glampingSetup(int $stock = 1): array
    {
        $unit = Unit::create([
            'name' => 'Glamping Date Lock',
            'type' => 'glamping',
            'price' => 500000.00,
            'capacity' => 2,
            'stock' => $stock,
        ]);

        $package = GlampingPackage::create([
            'code' => 'glamp_6h',
            'name' => 'Paket 6 Jam',
            'duration_hours' => 6,
            'price' => 150000.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [$unit, $package];
    }

    public function test_first_reservation_succeeds_and_second_conflicts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 07:00:00', 'Asia/Jakarta'));
        [$unit, $package] = $this->glampingSetup(1);
        $date = '2026-12-10';

        $first = $this->postJson('/api/reservations', [
            'visitor_name' => 'Budi Santoso',
            'visitor_whatsapp' => '081234567890',
            'visitor_email' => 'budi@example.com',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => $date,
            'total_price' => 1000000.00,
        ]);

        $first->assertStatus(201)->assertJsonPath('success', true);

        $second = $this->postJson('/api/reservations', [
            'visitor_name' => 'Ani Wijaya',
            'visitor_whatsapp' => '089876543210',
            'visitor_email' => 'ani@example.com',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => $date,
            'total_price' => 1000000.00,
        ]);

        $second->assertStatus(409)->assertJsonPath('success', false);

        Carbon::setTestNow();
    }

    public function test_overlapping_partial_dates_are_rejected(): void
    {
        // Dengan logika paket (durasi/jam otomatis), overlap berarti jendela waktu sama pada tanggal yang sama.
        Carbon::setTestNow(Carbon::parse('2026-12-01 07:00:00', 'Asia/Jakarta'));
        [$unit, $package] = $this->glampingSetup(1);
        $date = '2026-12-10';

        $this->postJson('/api/reservations', [
            'visitor_name' => 'First',
            'visitor_whatsapp' => '081111111111',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => $date,
        ])->assertStatus(201);

        $this->postJson('/api/reservations', [
            'visitor_name' => 'Second',
            'visitor_whatsapp' => '082222222222',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => $date,
        ])->assertStatus(409);

        Carbon::setTestNow();
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
            'package_id' => 'glamp_6h',
            'check_in_date' => now()->subDays(5)->toDateString(),
            'total_price' => 1000000.00,
        ]);

        $response->assertStatus(422);
    }
}
