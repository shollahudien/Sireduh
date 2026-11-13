<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TourismSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tourism_tables_and_core_columns_exist(): void
    {
        foreach ([
            'users', 'visitors', 'units', 'reservations', 'reservation_addons',
            'payments', 'tickets', 'products', 'pos_transactions',
            'pos_transaction_details',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasColumns('users', ['id', 'name', 'email', 'password', 'role']));
        $this->assertTrue(Schema::hasColumns('units', ['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']));
        $this->assertTrue(Schema::hasColumns('reservations', [
            'order_code', 'visitor_id', 'unit_id', 'check_in_date', 'check_out_date', 'total_price', 'status',
        ]));
        $this->assertTrue(Schema::hasColumns('tickets', ['reservation_id', 'unique_hash_sha256', 'is_used', 'used_at']));
        $this->assertTrue(Schema::hasColumns('products', ['id', 'name', 'category', 'price', 'type', 'stock']));
        $this->assertTrue(Schema::hasColumns('pos_transaction_details', ['id', 'pos_transaction_id', 'product_id', 'unit_id', 'quantity', 'subtotal']));
    }

    public function test_nullable_foreign_keys_for_ticket_layer(): void
    {
        $this->assertTrue(Schema::hasColumns('reservations', ['unit_id']));
        $unitColumn = Schema::getColumns('reservations');
        $reservationUnit = collect($unitColumn)->firstWhere('name', 'unit_id');
        $this->assertNotNull($reservationUnit);
        $this->assertTrue($reservationUnit['nullable']);

        $detailColumns = Schema::getColumns('pos_transaction_details');
        $detailUnit = collect($detailColumns)->firstWhere('name', 'unit_id');
        $detailProduct = collect($detailColumns)->firstWhere('name', 'product_id');
        $this->assertTrue($detailUnit['nullable']);
        $this->assertTrue($detailProduct['nullable']);
    }
}
