<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Unit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_belongs_to_visitor_and_unit_and_has_related_records(): void
    {
        $visitor = Visitor::create(['name' => 'Test Visitor', 'whatsapp_number' => '081234567890']);
        $unit = Unit::create(['name' => 'Tenda 1', 'type' => 'glamping', 'price' => 100000, 'capacity' => 2, 'stock' => 1]);
        $reservation = Reservation::create([
            'order_code' => 'SRT-TEST-1', 'visitor_id' => $visitor->id, 'unit_id' => $unit->id,
            'check_in_date' => '2026-12-01', 'check_out_date' => '2026-12-02', 'total_price' => 100000, 'status' => 'pending',
        ]);

        $this->assertTrue(method_exists($reservation, 'visitor'));
        $this->assertTrue(method_exists($reservation, 'unit'));
        $this->assertInstanceOf(Visitor::class, $reservation->visitor);
        $this->assertInstanceOf(Unit::class, $reservation->unit);
    }
}
