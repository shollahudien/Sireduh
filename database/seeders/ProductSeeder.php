<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Tiket Masuk Dewasa', 'category' => 'tiket_masuk', 'price' => 15000, 'type' => 'addon', 'stock' => 500],
            ['name' => 'Tiket Masuk Anak', 'category' => 'tiket_masuk', 'price' => 10000, 'type' => 'addon', 'stock' => 500],
            ['name' => 'Tiket Masuk Dewasa (Loket)', 'category' => 'tiket_masuk', 'price' => 15000, 'type' => 'pos', 'stock' => 500],
            ['name' => 'Tiket Masuk Anak (Loket)', 'category' => 'tiket_masuk', 'price' => 10000, 'type' => 'pos', 'stock' => 500],
            ['name' => 'Kopi Ijen Gayo (UMKM)', 'category' => 'produk_umkm', 'price' => 20000, 'type' => 'addon', 'stock' => 30],
            ['name' => 'Makanan Ringan Kacang Goreng', 'category' => 'makanan', 'price' => 12000, 'type' => 'pos', 'stock' => 50],
            ['name' => 'Es Teh Manis', 'category' => 'minuman', 'price' => 10000, 'type' => 'pos', 'stock' => 100],
            ['name' => 'Kaos Wisata Teduh', 'category' => 'baju_merchandise', 'price' => 85000, 'type' => 'pos', 'stock' => 40],
            ['name' => 'Mini Souvenir Tote Bag', 'category' => 'souvenir', 'price' => 25000, 'type' => 'pos', 'stock' => 60],
            ['name' => 'Paket Seduh Kopi Ijen', 'category' => 'produk_umkm', 'price' => 45000, 'type' => 'pos', 'stock' => 25],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                array_merge($product, ['for_kasir_makanan' => $product['category'] === 'makanan']),
            );
        }
    }
}
