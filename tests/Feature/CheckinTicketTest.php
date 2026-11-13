<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckinTicketTest extends TestCase
{
    use RefreshDatabase;

    private function checkin(): User
    {
        return User::create([
            'name' => 'Petugas Checkin',
            'email' => 'checkin-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'checkin',
        ]);
    }

    private function ticketProduct(string $name = 'Tiket Dewasa', int $stock = 100): Product
    {
        return Product::create([
            'name' => $name,
            'category' => 'tiket_masuk',
            'price' => 15000,
            'type' => 'pos',
            'stock' => $stock,
        ]);
    }

    public function test_checkin_gets_redirected_to_checkin_on_login(): void
    {
        $user = $this->checkin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret',
        ]);

        $response->assertRedirect('/checkin');
    }

    public function test_checkin_sees_ticket_page(): void
    {
        $this->actingAs($this->checkin())
            ->get('/checkin/tiket')
            ->assertOk()
            ->assertSee('Jual Tiket Masuk');
    }

    public function test_checkin_sees_only_ticket_products(): void
    {
        $this->ticketProduct('Tiket Dewasa');
        $this->ticketProduct('Tiket Anak', 100);

        Product::create(['name' => 'Makanan Ringan Kacang', 'category' => 'makanan', 'price' => 12000, 'type' => 'pos', 'stock' => 50]);
        Product::create(['name' => 'Es Teh', 'category' => 'minuman', 'price' => 10000, 'type' => 'pos', 'stock' => 50]);

        $response = $this->actingAs($this->checkin())->getJson('/api/pos/products');

        $response->assertOk();
        $categories = collect($response->json('data'))->pluck('category')->all();
        $this->assertNotEmpty($categories);
        $this->assertSame(array_fill(0, count($categories), 'tiket_masuk'), $categories);
    }

    public function test_checkin_checkout_rejects_non_ticket_and_unit_and_transfer(): void
    {
        $checkin = $this->checkin();
        $this->ticketProduct('Tiket Dewasa');

        $food = Product::create(['name' => 'Makanan', 'category' => 'makanan', 'price' => 12000, 'type' => 'pos', 'stock' => 50]);

        $unit = Unit::create(['name' => 'Tenda', 'type' => 'alat_wisata', 'price' => 50000, 'capacity' => 4, 'stock' => 1]);

        // non-ticket product rejected
        $this->actingAs($checkin)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [['product_id' => $food->id, 'quantity' => 1]],
        ])->assertStatus(422);

        // unit rental rejected
        $this->actingAs($checkin)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [['unit_id' => $unit->id, 'quantity' => 1]],
        ])->assertStatus(422);

        // transfer payment rejected
        $this->actingAs($checkin)->postJson('/api/pos/checkout', [
            'payment_method' => 'transfer',
            'items' => [['product_id' => Product::where('category', 'tiket_masuk')->value('id'), 'quantity' => 1]],
        ])->assertStatus(422);

        // cash & qris accepted
        foreach (['cash', 'qris'] as $method) {
            $this->actingAs($checkin)->postJson('/api/pos/checkout', [
                'payment_method' => $method,
                'items' => [['product_id' => Product::where('category', 'tiket_masuk')->value('id'), 'quantity' => 2]],
            ])->assertStatus(201)->assertJsonPath('success', true);
        }
    }
}
