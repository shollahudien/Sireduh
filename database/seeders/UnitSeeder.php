<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Tenda Premium Glamping',
                'type' => 'glamping',
                'price' => 350000,
                'capacity' => 4,
                'stock' => 6,
                'description' => 'Tenda glamping premium dengan kasur, listrik, dan kamar mandi dalam untuk 4 orang.',
            ],
            [
                'name' => 'Aula Bugar Baca',
                'type' => 'aula',
                'price' => 1500000,
                'capacity' => 20,
                'stock' => 1,
                'description' => 'Aula serbaguna untuk rapat, acara keluarga, dan kegiatan komunitas hingga 20 orang.',
            ],
            [
                'name' => 'Set Alat Pendakian',
                'type' => 'alat_wisata',
                'price' => 50000,
                'capacity' => 6,
                'stock' => 10,
                'description' => 'Paket sewa alat pendakian: tenda, sleeping bag, matras, dan carrier untuk 6 orang.',
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['name' => $unit['name']],
                $unit,
            );
        }
    }
}
