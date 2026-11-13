<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Master Data',
            'email' => 'admin@masterdata.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    private function kasir(): User
    {
        return User::create([
            'name' => 'Kasir Master Data',
            'email' => 'kasir@masterdata.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);
    }

    public function test_admin_can_list_and_filter_units(): void
    {
        Unit::create([
            'name' => 'Tenda Glamping A',
            'type' => 'glamping',
            'price' => 350000,
            'capacity' => 4,
            'stock' => 5,
        ]);
        Unit::create([
            'name' => 'Aula Bugar Baca',
            'type' => 'aula',
            'price' => 1500000,
            'capacity' => 20,
            'stock' => 1,
        ]);

        $this->actingAs($this->admin());

        $this->getJson('/api/admin/units?type=aula')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'aula');
    }

    public function test_admin_can_create_update_and_delete_unit(): void
    {
        $this->actingAs($this->admin());

        $created = $this->postJson('/api/admin/units', [
            'name' => 'Tenda Baru',
            'type' => 'glamping',
            'price' => 275000,
            'capacity' => 2,
            'stock' => 3,
            'description' => 'Tenda glamping standar',
        ]);
        $created->assertStatus(201)->assertJsonPath('data.name', 'Tenda Baru');

        $unitId = $created->json('data.id');

        $this->putJson("/api/admin/units/{$unitId}", ['price' => 300000])
            ->assertStatus(200)
            ->assertJsonPath('data.price', '300000.00');

        $this->patchJson("/api/admin/units/{$unitId}/stock", ['stock' => 9])
            ->assertStatus(200)
            ->assertJsonPath('data.stock', 9);

        $this->deleteJson("/api/admin/units/{$unitId}")->assertStatus(200);
        $this->assertDatabaseMissing('units', ['id' => $unitId]);
    }

    public function test_unit_validation_rejects_invalid_type_and_price(): void
    {
        $this->actingAs($this->admin());

        $this->postJson('/api/admin/units', [
            'name' => 'Unit Invalid',
            'type' => 'villa',
            'price' => -100,
            'capacity' => 0,
            'stock' => 1,
        ])->assertStatus(422);
    }

    public function test_admin_can_list_and_filter_products(): void
    {
        Product::create([
            'name' => 'Es Teh Manis',
            'category' => 'minuman',
            'price' => 10000,
            'type' => 'pos',
            'stock' => 100,
        ]);
        Product::create([
            'name' => 'Tiket Masuk Anak',
            'category' => 'tiket_masuk',
            'price' => 10000,
            'type' => 'addon',
            'stock' => 500,
        ]);

        $this->actingAs($this->admin());

        $this->getJson('/api/admin/products?category=tiket_masuk')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'tiket_masuk');
    }

    public function test_admin_can_create_product_with_default_type(): void
    {
        $this->actingAs($this->admin());

        $this->postJson('/api/admin/products', [
            'name' => 'Tiket Masuk Baru',
            'category' => 'tiket_masuk',
            'price' => 20000,
            'stock' => 100,
        ])->assertStatus(201)->assertJsonPath('data.type', 'addon');

        $this->postJson('/api/admin/products', [
            'name' => 'Kaos Teduh',
            'category' => 'baju_merchandise',
            'price' => 85000,
            'stock' => 20,
        ])->assertStatus(201)->assertJsonPath('data.type', 'pos');
    }

    public function test_admin_can_update_and_delete_product(): void
    {
        $this->actingAs($this->admin());

        $product = Product::create([
            'name' => 'Kopi Teduh',
            'category' => 'produk_umkm',
            'price' => 20000,
            'type' => 'pos',
            'stock' => 30,
        ]);

        $this->putJson("/api/admin/products/{$product->id}", ['price' => 22000])
            ->assertStatus(200)
            ->assertJsonPath('data.price', '22000.00');

        $this->patchJson("/api/admin/products/{$product->id}/stock", ['stock' => 45])
            ->assertStatus(200)
            ->assertJsonPath('data.stock', 45);

        $this->deleteJson("/api/admin/products/{$product->id}")->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_non_admin_cannot_access_admin_master_data(): void
    {
        $this->actingAs($this->kasir());

        $this->getJson('/api/admin/units')->assertStatus(403);
        $this->getJson('/api/admin/products')->assertStatus(403);
        $this->postJson('/api/admin/units', [
            'name' => 'Tenda Unauthorized',
            'type' => 'glamping',
            'price' => 100000,
            'capacity' => 2,
            'stock' => 1,
        ])->assertStatus(403);
    }

    public function test_guest_cannot_access_admin_master_data(): void
    {
        $this->getJson('/api/admin/units')->assertStatus(401);
        $this->postJson('/api/admin/products', [
            'name' => 'Produk Unauthorized',
            'category' => 'makanan',
            'price' => 10000,
            'stock' => 1,
        ])->assertStatus(401);
    }
}
