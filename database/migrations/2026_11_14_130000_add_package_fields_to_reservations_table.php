<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('package_id')->nullable()->after('unit_id');
            $table->string('package_name')->nullable()->after('package_id');
            $table->unsignedInteger('package_duration_hours')->nullable()->after('package_name');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['package_id', 'package_name', 'package_duration_hours']);
        });
    }
};
