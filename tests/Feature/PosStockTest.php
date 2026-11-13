<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosStockTest extends TestCase
{
    use RefreshDatabase;

    private function kasir(): User
    {
        return User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);
    }

    public function test_pos_checkout_deducts_product_and_unit_stock(): void
    {
        $officer = $this->kasir();

        $kopi = Product::create([
            'name' => 'Kopi Ijen',
            'category' => 'produk_umkm',
            'price' => 20000.00,
            'type' => 'pos',
            'stock' => 10,
        ]);

        $tenda = Unit::create([
            'name' => 'Sewa Tenda',
            'type' => 'alat_wisata',
            'price' => 100000.00,
            'capacity' => 6,
            'stock' => 5,
        ]);

        $this->actingAs($officer);

        $response = $this->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $kopi->id, 'quantity' => 2],
                ['unit_id' => $tenda->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', [
            'id' => $kopi->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('units', [
            'id' => $tenda->id,
            'stock' => 4,
        ]);

        $this->assertDatabaseHas('pos_transactions', [
            'user_id' => $officer->id,
        ]);
    }

    public function test_pos_checkout_rolls_back_when_product_out_of_stock(): void
    {
        $officer = $this->kasir();

        $kopi = Product::create([
            'name' => 'Kopi Ijen',
            'category' => 'produk_umkm',
            'price' => 20000.00,
            'type' => 'pos',
            'stock' => 1, // hanya 1, tapi kita pesan 5
        ]);

        $this->actingAs($officer);

        $before = $kopi->stock;

        $response = $this->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $kopi->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(422);

        // Stok tidak boleh berubah (rollback)
        $this->assertDatabaseHas('products', [
            'id' => $kopi->id,
            'stock' => $before,
        ]);

        $this->assertDatabaseCount('pos_transactions', 0);
    }

    public function test_pos_checkout_rejects_negative_quantity(): void
    {
        $officer = $this->kasir();

        $product = Product::create([
            'name' => 'Kopi Ijen',
            'category' => 'produk_umkm',
            'price' => 20000.00,
            'type' => 'pos',
            'stock' => 10,
        ]);

        $this->actingAs($officer);

        $response = $this->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $product->id, 'quantity' => -2],
            ],
        ]);

        $response->assertStatus(422);
    }
}
