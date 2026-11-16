<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('payments', function (Blueprint $table) use ($driver): void {
            if ($driver !== 'sqlite') {
                $table->string('proof_image')->nullable()->change();
            }
            $table->string('method')->default('manual_transfer')->after('reservation_id');
            $table->string('provider')->nullable()->after('method');
            $table->string('provider_order_id')->nullable()->after('provider');
            $table->string('provider_transaction_id')->nullable()->after('provider_order_id');
            $table->string('payment_type')->nullable()->after('provider_transaction_id');
            $table->string('snap_token')->nullable()->after('payment_type');
            $table->text('snap_redirect_url')->nullable()->after('snap_token');
            $table->string('va_number')->nullable()->after('snap_redirect_url');
            $table->text('qr_string')->nullable()->after('va_number');
            $table->timestamp('paid_at')->nullable()->after('verified_at');
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->json('gateway_payload')->nullable()->after('expires_at');
        });

        if ($driver === 'sqlite') {
            // sqlite: enum stored as text, proof_image left as-is (app handles null)
        } else {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','waiting_verification','verified','rejected','expired','failed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'method', 'provider', 'provider_order_id', 'provider_transaction_id', 'payment_type',
                'snap_token', 'snap_redirect_url', 'va_number', 'qr_string', 'paid_at', 'expires_at', 'gateway_payload',
            ]);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
