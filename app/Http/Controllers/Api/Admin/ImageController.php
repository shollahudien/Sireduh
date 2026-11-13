<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageController extends Controller
{
    /**
     * Upload satu/lebih gambar untuk unit sewa.
     */
    public function storeUnit(Request $request, Unit $unit): JsonResponse
    {
        return $this->storeMany($request, $unit, 'units');
    }

    /**
     * Upload satu/lebih gambar untuk produk (UMKM/F&B/Tiket dll).
     */
    public function storeProduct(Request $request, Product $product): JsonResponse
    {
        return $this->storeMany($request, $product, 'products');
    }

    /**
     * Hapus satu gambar milik unit.
     */
    public function destroyUnitImage(Unit $unit, Image $image): JsonResponse
    {
        return $this->destroyOne($image, $unit);
    }

    /**
     * Hapus satu gambar milik produk.
     */
    public function destroyProductImage(Product $product, Image $image): JsonResponse
    {
        return $this->destroyOne($image, $product);
    }

    /**
     * @return array<int, array{id:int, url:string, alt:string|null, order:int}>
     */
    protected function presentImages($model): array
    {
        return $model->images()
            ->get()
            ->map(fn (Image $image): array => [
                'id' => $image->id,
                'url' => Storage::disk('public')->url($image->path),
                'alt' => $image->alt,
                'order' => $image->order,
            ])
            ->values()
            ->all();
    }

    protected function storeMany(Request $request, Unit|Product $model, string $folder): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $created = [];

        try {
            foreach ($request->file('images') as $file) {
                $path = $file->store('uploads/images/'.$folder.'/'.$model->id, 'public');

                $image = $model->images()->create([
                    'path' => $path,
                    'alt' => $file->getClientOriginalName(),
                    'order' => ((int) $model->images()->max('order')) + 1,
                ]);

                $created[] = [
                    'id' => $image->id,
                    'url' => Storage::disk('public')->url($image->path),
                    'alt' => $image->alt,
                    'order' => $image->order,
                ];
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah gambar: '.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($created).' gambar berhasil diunggah.',
            'data' => $this->presentImages($model),
        ], 201);
    }

    protected function destroyOne(Image $image, Unit|Product $owner): JsonResponse
    {
        // Pastikan gambar benar-benar milik resource yang diakses (anti-IDOR).
        if ($image->imageable_type !== $owner::class || (int) $image->imageable_id !== (int) $owner->id) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar tidak ditemukan pada resource ini.',
            ], 404);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gambar dihapus.',
            'data' => $this->presentImages($owner),
        ]);
    }
}
