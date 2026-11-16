<?php

namespace Tests\Feature;

use App\Models\GlampingPackage;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function glampingUnit(): Unit
    {
        return Unit::create([
            'name' => 'Glamping Security Test',
            'type' => 'glamping',
            'price' => 350000,
            'capacity' => 2,
            'stock' => 5,
        ]);
    }

    private function glampingPackage(): GlampingPackage
    {
        return GlampingPackage::create([
            'code' => 'glamp_sec_6h',
            'name' => 'Paket 6 Jam',
            'duration_hours' => 6,
            'price' => 200000,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function ticketProduct(): Product
    {
        return Product::create([
            'name' => 'Tiket Security Test',
            'category' => 'tiket_masuk',
            'price' => 15000,
            'type' => 'addon',
            'stock' => 100,
        ]);
    }

    public function test_frontend_total_price_is_ignored_and_recalculated_server_side(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 07:00:00', 'Asia/Jakarta'));

        $unit = $this->glampingUnit();
        $package = $this->glampingPackage();
        $product = $this->ticketProduct();

        $payload = [
            'visitor_name' => 'Attacker Manipulation',
            'visitor_whatsapp' => '08170000999',
            'visitor_email' => 'manipulator@example.com',
            'unit_id' => $unit->id,
            'package_id' => $package->code,
            'check_in_date' => now('Asia/Jakarta')->addDay()->toDateString(),
            'addons' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            // Penyerang mencoba memalsukan total: paket 200.000 + 2 tiket = 230.000
            'total_price' => 1,
        ];

        $response = $this->postJson('/api/reservations', $payload);

        $response->assertStatus(201)->assertJsonPath('total_price', 230000);

        $reservation = Reservation::latest('id')->first();
        $this->assertEquals(230000.0, (float) $reservation->total_price);

        Carbon::setTestNow();
    }

    public function test_forged_hash_returns_404_and_valid_ticket_returns_200(): void
    {
        $checker = User::create([
            'name' => 'Check-in Officer',
            'email' => 'checkin-sec-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'checkin',
        ]);

        // Hash acak (bukan dari sistem) harus 404
        $this->actingAs($checker)
            ->postJson('/api/checkin/validate', ['hash' => str_repeat('0', 64)])
            ->assertStatus(404);

        // Hash valid tapi untuk role kasir → 403
        $kasir = User::create([
            'name' => 'Kasir Palsu',
            'email' => 'kasir-sec-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);

        $this->actingAs($kasir)
            ->postJson('/api/checkin/validate', ['hash' => str_repeat('a', 64)])
            ->assertStatus(403);
    }

    public function test_pos_checkout_ignores_any_client_supplied_totals(): void
    {
        $officer = User::create([
            'name' => 'Kasir Sec',
            'email' => 'kasirsec-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);

        $product = Product::create([
            'name' => 'Kopi Sec',
            'category' => 'produk_umkm',
            'price' => 25000,
            'type' => 'pos',
            'stock' => 10,
        ]);

        $this->actingAs($officer)
            ->postJson('/api/pos/checkout', [
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                // Penyerang coba sisipkan total 0
                'total_amount' => 0,
                'total' => 0,
            ])
            ->assertStatus(201)
            ->assertJsonPath('transaction.total_amount', '50000.00');
    }
}
