<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'method', 'provider', 'provider_order_id', 'provider_transaction_id', 'payment_type',
        'snap_token', 'snap_redirect_url', 'va_number', 'qr_string',
        'proof_image', 'status', 'verified_at', 'paid_at', 'expires_at', 'gateway_payload',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'gateway_payload' => 'array',
            'status' => 'string',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
