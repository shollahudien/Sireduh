<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glamping_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('duration_hours');
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('glamping_packages')->insert([
            ['code' => 'glamping_6jam', 'name' => 'Paket 6 Jam', 'duration_hours' => 6, 'price' => 50000, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'glamping_12jam', 'name' => 'Paket 12 Jam', 'duration_hours' => 12, 'price' => 90000, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'glamping_24jam', 'name' => 'Paket 1 Hari (24 Jam)', 'duration_hours' => 24, 'price' => 150000, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'glamping_menginap', 'name' => 'Paket Menginap', 'duration_hours' => 24, 'price' => 350000, 'is_active' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('glamping_packages');
    }
};
