<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Pencari',
            'email' => 'admin-search-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
    }

    private function makeReservation(array $attributes = []): Reservation
    {
        $visitor = Visitor::create([
            'name' => $attributes['visitor_name'] ?? 'Budi Pencari Uji',
            'whatsapp_number' => $attributes['whatsapp_number'] ?? '628100000001',
            'email' => null,
        ]);

        $unit = Unit::create([
            'name' => $attributes['unit_name'] ?? 'Tenda Uji Search',
            'type' => 'glamping',
            'price' => 350000,
            'capacity' => 2,
            'stock' => 3,
            'description' => 'Unit untuk test pencarian',
        ]);

        return Reservation::create([
            'order_code' => $attributes['order_code'] ?? 'SRCH-'.strtoupper(uniqid()),
            'visitor_id' => $visitor->id,
            'unit_id' => $unit->id,
            'check_in_date' => now()->addDays(2)->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'total_price' => 350000,
            'status' => $attributes['status'] ?? 'pending',
        ]);
    }

    public function test_admin_finds_reservation_by_order_code_and_visitor_name(): void
    {
        $reservation = $this->makeReservation([
            'order_code' => 'SRCH-CODE-123',
            'visitor_name' => 'Siti Unik Pencari',
        ]);
        $admin = $this->admin();

        // Cari via kode pesanan
        $byCode = $this->actingAs($admin)->getJson('/api/admin/search?q=SRCH-CODE-123');
        $byCode->assertOk()->assertJsonPath('total', 1);
        $byCode->assertJsonPath('groups.0.key', 'reservations');
        $byCode->assertJsonPath('groups.0.items.0.title', 'SRCH-CODE-123');

        // Cari via nama tamu
        $byName = $this->actingAs($admin)->getJson('/api/admin/search?q=Siti Unik Pencari');
        $byName->assertOk()->assertJsonPath('groups.0.items.0.title', $reservation->order_code);
    }

    public function test_admin_finds_reservation_by_visitor_whatsapp_number(): void
    {
        $this->makeReservation(['whatsapp_number' => '6281234567890']);
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/admin/search?q=6281234567890')
            ->assertOk()
            ->assertJsonPath('groups.0.key', 'reservations');
    }

    public function test_admin_finds_units_and_products(): void
    {
        Unit::create([
            'name' => 'Glamping Tebing Kuda',
            'type' => 'glamping',
            'price' => 500000,
            'capacity' => 4,
            'stock' => 2,
            'description' => 'Unit tebing',
        ]);

        Product::create([
            'name' => 'Kopi Teduh Robusta',
            'category' => 'minuman',
            'price' => 15000,
            'type' => 'pos',
            'stock' => 50,
            'for_kasir_makanan' => true,
        ]);

        $admin = $this->admin();

        $units = $this->actingAs($admin)->getJson('/api/admin/search?q=Tebing');
        $units->assertOk()->assertJsonPath('groups.0.key', 'units');
        $units->assertJsonPath('groups.0.items.0.title', 'Glamping Tebing Kuda');
        $units->assertJsonPath('groups.0.items.0.url', route('admin.units'));

        $products = $this->actingAs($admin)->getJson('/api/admin/search?q=Robusta');
        $products->assertOk()->assertJsonPath('groups.0.key', 'products');
        $products->assertJsonPath('groups.0.items.0.url', route('admin.products'));
    }

    public function test_admin_finds_users_by_name_and_email(): void
    {
        $target = User::create([
            'name' => 'Kasir Zulfikar',
            'email' => 'zulfikar-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);
        $admin = $this->admin();

        $byEmail = $this->actingAs($admin)->getJson('/api/admin/search?q='.substr($target->email, 0, 9));
        $byEmail->assertOk()->assertJsonPath('groups.0.key', 'users');
        $byEmail->assertJsonFragment(['title' => 'Kasir Zulfikar']);

        $byName = $this->actingAs($admin)->getJson('/api/admin/search?q=Zulfikar');
        $byName->assertOk()->assertJsonFragment(['url' => route('admin.users')]);
    }

    public function test_admin_finds_pending_payment_by_order_code(): void
    {
        $reservation = $this->makeReservation(['order_code' => 'SRCH-PAY-99', 'status' => 'waiting_verification']);
        Payment::create([
            'reservation_id' => $reservation->id,
            'proof_image' => 'proofs/uji.jpg',
            'status' => 'pending',
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->getJson('/api/admin/search?q=SRCH-PAY-99');
        $response->assertOk();

        // Reservasi & pembayaran tertunda sama-sama ketemu
        $keys = collect($response->json('groups'))->pluck('key')->all();
        $this->assertContains('reservations', $keys);
        $this->assertContains('payments', $keys);
        $response->assertJsonPath('groups.1.items.0.url', route('admin.verification'));
    }

    public function test_admin_finds_activity_log_entries(): void
    {
        ActivityLog::create([
            'user_id' => null,
            'user_name' => 'Petugas Unik Log',
            'user_role' => 'checkin',
            'ip' => '127.0.0.1',
            'method' => 'POST',
            'path' => 'checkin/scan-uji',
            'route' => null,
            'status' => 200,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/admin/search?q=scan-uji')
            ->assertOk()
            ->assertJsonPath('groups.0.key', 'activity')
            ->assertJsonPath('groups.0.items.0.url', route('admin.activity'));
    }

    public function test_query_shorter_than_two_characters_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/admin/search?q=a')
            ->assertStatus(422);

        $this->actingAs($admin)->getJson('/api/admin/search')
            ->assertStatus(422);
    }

    public function test_non_admin_roles_are_forbidden(): void
    {
        $kasir = User::create([
            'name' => 'Kasir Terlarang',
            'email' => 'kasir-search-'.uniqid().'@simateduh.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
        ]);

        $this->actingAs($kasir)->getJson('/api/admin/search?q=reservasi')
            ->assertStatus(403);
    }

    public function test_guest_is_unauthenticated(): void
    {
        $this->getJson('/api/admin/search?q=reservasi')
            ->assertStatus(401);
    }
}
