<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder khusus untuk UAT / thesis defense.
 * Menyediakan data yang langsung bisa dipakai untuk:
 *   - Admin verifikasi pembayaran (reservasi waiting_verification + bukti dummy)
 *   - Petugas check-in memindai tiket (reservasi paid + tiket SHA-256 valid)
 */
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users dengan role spesifik
        $admin = User::updateOrCreate(
            ['email' => 'admin@simateduh.test'],
            [
                'name' => 'Admin UAT',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'remember_token' => Str::random(10),
            ]
        );

        $kasir = User::updateOrCreate(
            ['email' => 'kasir@simateduh.test'],
            [
                'name' => 'Kasir UAT',
                'password' => Hash::make('kasir123'),
                'role' => User::ROLE_KASIR,
                'remember_token' => Str::random(10),
            ]
        );

        $checkin = User::updateOrCreate(
            ['email' => 'checkin@simateduh.test'],
            [
                'name' => 'Petugas Check-in UAT',
                'password' => Hash::make('checkin123'),
                'role' => User::ROLE_CHECKIN,
                'remember_token' => Str::random(10),
            ]
        );

        // 2. Units: Glamping 500k, Aula 2M, Alat Tenda 100k
        $glamping = Unit::updateOrCreate(
            ['name' => 'Glamping Premium UAT'],
            [
                'type' => 'glamping',
                'price' => 500000,
                'capacity' => 4,
                'stock' => 5,
                'description' => 'Unit glamping untuk pengujian UAT (harga 500k).',
            ]
        );

        $aula = Unit::updateOrCreate(
            ['name' => 'Aula Serbaguna UAT'],
            [
                'type' => 'aula',
                'price' => 2000000,
                'capacity' => 50,
                'stock' => 1,
                'description' => 'Aula untuk pengujian UAT (harga 2M).',
            ]
        );

        $tenda = Unit::updateOrCreate(
            ['name' => 'Alat Tenda UAT'],
            [
                'type' => 'alat_wisata',
                'price' => 100000,
                'capacity' => 6,
                'stock' => 10,
                'description' => 'Sewa alat tenda untuk pengujian UAT (harga 100k).',
            ]
        );

        // 3. Products: 5 produk sesuai spesifikasi
        $kopi = Product::updateOrCreate(
            ['name' => 'Kopi Ijen (UMKM)'],
            ['category' => 'produk_umkm', 'price' => 20000, 'type' => 'pos', 'stock' => 50]
        );

        $kaos = Product::updateOrCreate(
            ['name' => 'Kaos Teduh (Merchandise)'],
            ['category' => 'baju_merchandise', 'price' => 85000, 'type' => 'pos', 'stock' => 40]
        );

        $nasi = Product::updateOrCreate(
            ['name' => 'Nasi Goreng (Makanan)'],
            ['category' => 'makanan', 'price' => 25000, 'type' => 'pos', 'stock' => 30]
        );

        $esTeh = Product::updateOrCreate(
            ['name' => 'Es Teh (Minuman)'],
            ['category' => 'minuman', 'price' => 10000, 'type' => 'pos', 'stock' => 100]
        );

        $tiketMasuk = Product::updateOrCreate(
            ['name' => 'Tiket Masuk'],
            ['category' => 'tiket_masuk', 'price' => 15000, 'type' => 'addon', 'stock' => 500]
        );

        // 4. Reservasi menunggu verifikasi + bukti pembayaran dummy
        $visitor1 = Visitor::create([
            'name' => 'Budi Santoso (UAT)',
            'whatsapp_number' => '081234567890',
            'email' => 'budi.uat@example.com',
        ]);

        $resWaiting = Reservation::create([
            'order_code' => 'SIRES-UATWAIT1',
            'visitor_id' => $visitor1->id,
            'unit_id' => $glamping->id,
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(4)->toDateString(),
            'total_price' => 500000,
            'status' => 'waiting_verification',
        ]);

        Payment::create([
            'reservation_id' => $resWaiting->id,
            'proof_image' => 'reservations/proofs/dummy-payment.jpg',
            'status' => 'pending',
            'verified_at' => null,
        ]);

        // 5. Reservasi paid + tiket SHA-256 valid untuk scan check-in
        $visitor2 = Visitor::create([
            'name' => 'Ani Wijaya (UAT)',
            'whatsapp_number' => '089876543210',
            'email' => 'ani.uat@example.com',
        ]);

        $hash = hash('sha256', 'SIRES-UATPAID1');

        $resPaid = Reservation::create([
            'order_code' => 'SIRES-UATPAID1',
            'visitor_id' => $visitor2->id,
            'unit_id' => $glamping->id,
            'check_in_date' => now()->addDays(5)->toDateString(),
            'check_out_date' => now()->addDays(6)->toDateString(),
            'total_price' => 500000,
            'status' => 'paid',
        ]);

        Ticket::create([
            'reservation_id' => $resPaid->id,
            'unique_hash_sha256' => $hash,
            'is_used' => false,
        ]);

        $this->command?->info('TestingSeeder selesai: 3 user, 3 unit, 5 produk, 2 reservasi (waiting_verification & paid).');
        $this->command?->info('Tiket check-in hash: '.$hash);
    }
}
