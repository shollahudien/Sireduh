<?php

namespace Tests\Feature;

use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasirMakananTest extends TestCase
{
    use RefreshDatabase;

    private function makananUser(): User
    {
        return User::create([
            'name' => 'Kasir Makanan',
            'email' => 'makan-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir_makanan',
        ]);
    }

    private function product(string $category, string $name, string $type = 'pos', bool $forKasirMakanan = false): Product
    {
        return Product::create([
            'name' => $name,
            'category' => $category,
            'price' => 10000,
            'type' => $type,
            'stock' => 50,
            'for_kasir_makanan' => $forKasirMakanan,
        ]);
    }

    public function test_kasir_makanan_login_redirects_to_kasir_makanan(): void
    {
        $user = $this->makananUser();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret']);

        $response->assertRedirect('/kasir-makanan');
    }

    public function test_kasir_makanan_can_access_page_but_not_pos(): void
    {
        $user = $this->makananUser();

        $this->actingAs($user)->get('/kasir-makanan')->assertOk();
        $this->actingAs($user)->get('/pos')->assertRedirect('/');
    }

    public function test_catalog_returns_only_makanan_for_kasir_makanan(): void
    {
        $user = $this->makananUser();
        $this->product('makanan', 'Nasi Uduk', 'pos', true);
        $this->product('minuman', 'Es Teh');
        $this->product('baju_merchandise', 'Kaos');
        $this->product('tiket_masuk', 'Tiket Dewasa', 'addon');

        $response = $this->actingAs($user)->getJson('/api/pos/products');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('makanan', $response->json('data.0.category'));
    }

    public function test_riwayat_hanya_menampilkan_transaksi_milik_kasir_sendiri(): void
    {
        $makanan = $this->makananUser();

        $kasirUtama = User::create(['name' => 'K1', 'email' => 'k1-'.uniqid().'@simateduh.test', 'password' => bcrypt('secret'), 'role' => 'kasir']);

        // Transaksi kasir utama — tidak boleh terlihat oleh kasir makanan
        PosTransaction::create(['user_id' => $kasirUtama->id, 'total_amount' => 99999, 'payment_method' => 'cash']);

        // Transaksi kasir makanan — harus terlihat
        PosTransaction::create(['user_id' => $makanan->id, 'total_amount' => 12000, 'payment_method' => 'qris']);

        $response = $this->actingAs($makanan)->getJson('/api/pos/riwayat');

        $response->assertOk()
            ->assertJsonPath('summary.total_amount', 12000)
            ->assertJsonPath('summary.qris.total', 12000)
            ->assertJsonPath('summary.cash.count', 0)
            ->assertJsonPath('summary.cash.total', 0)
            ->assertJsonCount(1, 'transactions')
            ->assertJsonPath('transactions.0.total_amount', 12000);
    }

    public function test_admin_can_enable_specific_minuman_for_kasir_makanan(): void
    {
        $user = $this->makananUser();

        $minumanDefault = $this->product('minuman', 'Es Teh');            // default false
        $minumanEnabled = $this->product('minuman', 'Es Jeruk', 'pos', true);
        $this->product('makanan', 'Nasi Uduk', 'pos', true);

        $response = $this->actingAs($user)->getJson('/api/pos/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertContains('Es Jeruk', $names);
        $this->assertContains('Nasi Uduk', $names);
        $this->assertNotContains('Es Teh', $names);

        // Checkout with enabled minuman
        $checkout = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $minumanEnabled->id, 'quantity' => 1],
            ],
        ]);
        $checkout->assertStatus(201)->assertJsonPath('success', true);

        // Checkout with disabled minuman is rejected
        $rejected = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $minumanDefault->id, 'quantity' => 1],
            ],
        ]);
        $rejected->assertStatus(422);
    }

    public function test_kasir_makanan_checkout_rejects_non_makanan_and_units(): void
    {
        $user = $this->makananUser();
        $nasemak = $this->product('makanan', 'Nasi Goreng', 'pos', true);

        $minuman = $this->product('minuman', 'Kumary');

        $unit = Unit::create([
            'name' => 'Tenda',
            'type' => 'alat_wisata',
            'price' => 50000,
            'capacity' => 2,
            'stock' => 1,
        ]);

        // Array berdasarkan product_id yang valid, non-makanan
        $response = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $minuman->id, 'quantity' => 1],
            ],
        ]);
        $response->assertStatus(422);

        // Unit render ditolak
        $response2 = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['unit_id' => $unit->id, 'quantity' => 1],
            ],
        ]);
        $response2->assertStatus(422);

        // Makanan tetap boleh
        $response3 = $this->actingAs($user)->postJson('/api/pos/checkout', [
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $nasemak->id, 'quantity' => 2],
            ],
        ]);
        $response3->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('transaction.total_amount', '20000.00');
    }
}
