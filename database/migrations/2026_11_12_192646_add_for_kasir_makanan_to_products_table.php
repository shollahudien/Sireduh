<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('for_kasir_makanan')->default(false)->after('stock');
        });

        // Pertahankan perilaku lama: kasir makanan hanya bisa menjual makanan
        DB::table('products')
            ->where('category', 'makanan')
            ->update(['for_kasir_makanan' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('for_kasir_makanan');
        });
    }
};
