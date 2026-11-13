<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait PresentsImages
{
    /**
     * @return array<int, array{id:int, url:string, alt:string|null, order:int}>
     */
    protected function presentImages(Model $model): array
    {
        return $model->images
            ->map(fn ($image): array => [
                'id' => $image->id,
                'url' => Storage::disk('public')->url($image->path),
                'alt' => $image->alt,
                'order' => $image->order,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function withImages(Model $model): array
    {
        $data = $model->toArray();
        $data['images'] = $this->presentImages($model);

        return $data;
    }
}
