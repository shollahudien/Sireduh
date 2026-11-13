<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            VisitorSeeder::class,
            ProductSeeder::class,
            UserSeeder::class,
            ImageSeeder::class,
        ]);
    }
}
