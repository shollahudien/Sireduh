<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code', 'visitor_id', 'unit_id', 'package_id', 'package_name', 'package_duration_hours',
        'check_in_date', 'check_in_time', 'check_out_date', 'check_out_time',
        'total_price', 'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'package_duration_hours' => 'integer',
            'total_price' => 'decimal:2',
            'status' => 'string',
        ];
    }

    public function checkInAt(): ?Carbon
    {
        if (! $this->check_in_date) {
            return null;
        }

        return Carbon::parse($this->check_in_date->format('Y-m-d').' '.($this->check_in_time ?? '00:00:00'));
    }

    public function checkOutAt(): ?Carbon
    {
        if (! $this->check_out_date) {
            return null;
        }

        return Carbon::parse($this->check_out_date->format('Y-m-d').' '.($this->check_out_time ?? '23:59:59'));
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ReservationAddon::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    /**
     * Pastikan reservasi memiliki e-ticket QR (SHA-256) untuk discanner check-in.
     */
    public function ensureTicket(): Ticket
    {
        /** @var Ticket $ticket */
        $ticket = $this->ticket()->firstOrCreate([], [
            'unique_hash_sha256' => hash('sha256', $this->order_code.'|'.Str::uuid()),
            'is_used' => false,
        ]);

        return $ticket;
    }
}
