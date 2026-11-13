<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedPendingReservation(): Reservation
    {
        $visitor = Visitor::create([
            'name' => 'Payment Test',
            'whatsapp_number' => '081700000001',
            'email' => 'payment-test@example.com',
        ]);

        $unit = Unit::create([
            'name' => 'Tenda Pembayaran Test',
            'type' => 'glamping',
            'price' => 300000,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $product = Product::create([
            'name' => 'Tiket Masuk Dewasa',
            'category' => 'tiket_masuk',
            'price' => 15000,
            'type' => 'addon',
            'stock' => 500,
        ]);

        return Reservation::create([
            'order_code' => 'SIRES-TESTPAY',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => '2027-02-01',
            'check_out_date' => '2027-02-02',
            'total_price' => 315000,
            'status' => 'pending',
        ]);
    }

    public function test_payment_page_and_api_show_order_details(): void
    {
        $reservation = $this->seedPendingReservation();

        $this->get('/pembayaran/'.$reservation->order_code)
            ->assertOk()
            ->assertSee($reservation->order_code, false);

        $this->getJson('/api/reservations/'.$reservation->order_code.'/payment')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reservation.order_code', 'SIRES-TESTPAY')
            ->assertJsonPath('reservation.unit_name', 'Tenda Pembayaran Test')
            ->assertJsonPath('reservation.status', 'pending');
    }

    public function test_upload_proof_moves_reservation_to_waiting_verification(): void
    {
        Storage::fake('local');

        $reservation = $this->seedPendingReservation();

        $response = $this->post('/api/payments/upload', [
            'reservation_id' => $reservation->id,
            'proof_image' => UploadedFile::fake()->image('bukti.png'),
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertSame('waiting_verification', $reservation->fresh()->status);
    }
}
