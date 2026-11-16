<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aula_packages', function (Blueprint $table) {
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
        DB::table('aula_packages')->insert([
            ['code' => 'aula_setengah_hari', 'name' => 'Paket Setengah Hari (4 Jam)', 'duration_hours' => 4, 'price' => 500000, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'aula_seharian', 'name' => 'Paket Seharian (8 Jam)', 'duration_hours' => 8, 'price' => 900000, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'aula_full_day', 'name' => 'Paket Full Day (12 Jam)', 'duration_hours' => 12, 'price' => 1500000, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('aula_packages');
    }
};
