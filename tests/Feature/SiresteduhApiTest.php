<?php

namespace Tests\Feature;

use App\Models\GlampingPackage;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SiresteduhApiTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Modul 1 & 2 — Ketersediaan Unit & Date Locking
    // ----------------------------------------------------------------
    public function test_modules_1_and_2_availability_and_checkout_locking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 07:00:00', 'Asia/Jakarta'));

        $unit = Unit::create([
            'name' => 'Glamping Deluxe 1',
            'type' => 'glamping',
            'price' => 350000.00,
            'capacity' => 2,
            'stock' => 1,
        ]);

        $package = GlampingPackage::create([
            'code' => 'glamp_test_6h',
            'name' => 'Paket 6 Jam',
            'duration_hours' => 6,
            'price' => 200000.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Availability endpoint harus mengembalikan unit yang tersedia
        $avail = $this->getJson('/api/availability?check_in=2026-12-10&package_id='.$package->code.'&type=glamping');
        $avail->assertStatus(200)
            ->assertJsonPath('data.0.id', $unit->id);

        // Reservasi pertama harus berhasil (201)
        $first = $this->postJson('/api/reservations', [
            'visitor_name' => 'Budi Santoso',
            'visitor_whatsapp' => '081234567890',
            'visitor_email' => 'budi@example.com',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => '2026-12-10',
            'total_price' => 700000.00,
        ]);
        $first->assertStatus(201)->assertJsonPath('success', true);

        // Reservasi kedua untuk unit + jadwal paket yang sama harus ditolak (409)
        $conflict = $this->postJson('/api/reservations', [
            'visitor_name' => 'Ani Wijaya',
            'visitor_whatsapp' => '089876543210',
            'visitor_email' => 'ani@example.com',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => '2026-12-10',
            'total_price' => 700000.00,
        ]);
        $conflict->assertStatus(409);

        Carbon::setTestNow();
    }

    // ----------------------------------------------------------------
    // Modul 3 & 4 — Verifikasi Pembayaran & E-Ticket
    // ----------------------------------------------------------------
    public function test_module_3_and_4_payment_verification_and_eticket_generation(): void
    {
        $unit = Unit::create([
            'name' => 'Glamping Standard 2',
            'type' => 'glamping',
            'price' => 250000.00,
            'capacity' => 2,
            'stock' => 2,
        ]);

        $visitor = Visitor::create([
            'name' => 'Siti Aminah',
            'whatsapp_number' => '081122334455',
            'email' => 'siti@example.com',
        ]);

        $reservation = Reservation::create([
            'order_code' => 'SIRES-TEST01',
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => '2026-12-10',
            'check_out_date' => '2026-12-11',
            'total_price' => 250000.00,
            'status' => 'waiting_verification',
        ]);

        // Buat bukti pembayaran
        Payment::create([
            'reservation_id' => $reservation->id,
            'proof_image' => 'reservations/proofs/test.jpg',
            'status' => 'pending',
            'verified_at' => null,
        ]);

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/verify/{$reservation->id}");
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('reservation.status', 'paid');

        $this->assertDatabaseHas('tickets', [
            'reservation_id' => $reservation->id,
            'is_used' => false,
        ]);

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'status' => 'verified',
        ]);
    }

    // ----------------------------------------------------------------
    // Modul 5 — POS: Produk & Checkout
    // ----------------------------------------------------------------
    public function test_module_5_pos_products_and_checkout(): void
    {
        $officer = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);

        $product = Product::create([
            'name' => 'Kopi Teduh',
            'category' => 'produk_umkm',
            'price' => 25000.00,
            'type' => 'pos',
            'stock' => 10,
        ]);

        $this->actingAs($officer);

        // GET produk POS
        $products = $this->getJson('/api/pos/products');
        $products->assertStatus(200)
            ->assertJsonPath('data.0.id', $product->id);

        // Checkout 2 item
        $checkoutRes = $this->postJson('/api/pos/checkout', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => 'cash',
        ]);

        $checkoutRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('transaction.total_amount', '50000.00');

        // Stok harus berkurang dari 10 → 8
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);
    }

    // ----------------------------------------------------------------
    // Modul 6 — QR Check-In
    // ----------------------------------------------------------------
    public function test_module_6_qr_checkin_validation(): void
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

        $checkinOfficer = User::create([
            'name' => 'Petugas Checkin',
            'email' => 'checkin@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'checkin',
        ]);

        $this->actingAs($checkinOfficer);

        // Scan pertama → sukses
        $res1 = $this->postJson('/api/checkin/validate', ['hash' => $hash]);
        $res1->assertStatus(200)->assertJsonPath('success', true);

        // Scan kedua → ditolak (tiket sudah dipakai)
        $res2 = $this->postJson('/api/checkin/validate', ['hash' => $hash]);
        $res2->assertStatus(409)->assertJsonPath('success', false);
    }
}
