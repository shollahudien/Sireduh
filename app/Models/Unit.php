<?php

namespace App\Models;

use App\Services\ChatbotService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

class Unit extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Data unit berubah (termasuk harga) → konteks harga chatbot harus segar
        static::saved(fn () => Cache::forget(ChatbotService::CONTEXT_CACHE_KEY));
        static::deleted(fn () => Cache::forget(ChatbotService::CONTEXT_CACHE_KEY));
    }

    protected $fillable = [
        'name', 'type', 'price', 'capacity', 'stock', 'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'capacity' => 'integer',
            'stock' => 'integer',
            'description' => 'string',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function posTransactionDetails(): HasMany
    {
        return $this->hasMany(PosTransactionDetail::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order')->orderBy('id');
    }
}
