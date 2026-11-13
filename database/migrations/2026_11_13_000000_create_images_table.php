<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel 'images' kosong & tanpa kolom dari stub proses lain — aman di-drop karena tidak berisi data.
        Schema::dropIfExists('images');

        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');
            $table->string('path', 500);
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('alt', 255)->nullable();
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
