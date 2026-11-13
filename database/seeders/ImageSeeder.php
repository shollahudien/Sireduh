<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    public function run(): void
    {
        $attach = [
            'Tenda Premium Glamping' => ['seed/images/glamping-1.svg', 'seed/images/glamping-2.svg', 'seed/images/glamping-3.svg'],
            'Aula Bugar Baca' => ['seed/images/aula-1.svg', 'seed/images/aula-2.svg'],
            'Set Alat Pendakian' => ['seed/images/alat-1.svg', 'seed/images/alat-2.svg', 'seed/images/alat-3.svg'],
            'Kopi Ijen Gayo (UMKM)' => ['seed/images/kopi-ijen-1.svg', 'seed/images/kopi-ijen-2.svg'],
            'Paket Seduh Kopi Ijen' => ['seed/images/paket-kopi-1.svg'],
            'Es Teh Manis' => ['seed/images/teh-1.svg'],
            'Makanan Ringan Kacang Goreng' => ['seed/images/kacang-1.svg'],
            'Kaos Wisata Teduh' => ['seed/images/kaos-1.svg'],
            'Mini Souvenir Tote Bag' => ['seed/images/totebag-1.svg'],
            'Tiket Masuk Dewasa' => ['seed/images/tiket-dewasa-1.svg'],
            'Tiket Masuk Anak' => ['seed/images/tiket-anak-1.svg'],
        ];

        foreach ($attach as $name => $files) {
            $model = Unit::query()->where('name', $name)->first()
                ?? Product::query()->where('name', $name)->first();

            if ($model === null) {
                \Log::warning('ImageSeeder: nama tidak ditemukan', ['name' => $name]);

                continue;
            }

            foreach ($files as $i => $path) {
                Image::updateOrCreate(
                    [
                        'imageable_type' => $model::class,
                        'imageable_id' => $model->id,
                        'path' => $path,
                    ],
                    [
                        'order' => $i + 1,
                        'alt' => $name,
                    ],
                );
            }
        }
    }
}
