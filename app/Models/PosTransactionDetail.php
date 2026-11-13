<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_transaction_id', 'product_id', 'unit_id', 'quantity', 'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'unit_id' => 'integer',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
