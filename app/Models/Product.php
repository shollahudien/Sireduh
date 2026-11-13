<?php

namespace App\Models;

use App\Services\ChatbotService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Data produk berubah (termasuk harga) → konteks harga chatbot harus segar
        static::saved(fn () => Cache::forget(ChatbotService::CONTEXT_CACHE_KEY));
        static::deleted(fn () => Cache::forget(ChatbotService::CONTEXT_CACHE_KEY));
    }

    protected $fillable = [
        'name', 'category', 'price', 'type', 'stock', 'for_kasir_makanan',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'for_kasir_makanan' => 'boolean',
            'type' => 'string',
            'category' => 'string',
        ];
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ReservationAddon::class);
    }

    public function posDetails(): HasMany
    {
        return $this->hasMany(PosTransactionDetail::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order')->orderBy('id');
    }
}
