<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'waiting_verification', 'paid', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['unit_id', 'check_in_date', 'check_out_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
