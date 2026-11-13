<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $fillable = [
        'imageable_type', 'imageable_id', 'path', 'order', 'alt',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Public URL for the stored image (public disk → /storage/...).
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
