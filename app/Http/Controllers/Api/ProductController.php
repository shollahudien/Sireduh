<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use PresentsImages;

    /**
     * List products publicly (used by the public checkout page for
     * Tiket Masuk and UMKM add-ons).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'in:tiket_masuk,makanan,minuman,souvenir,baju_merchandise,produk_umkm'],
            'type' => ['nullable', 'in:addon,pos'],
        ]);

        $products = Product::query()
            ->when($validated['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('name')
            ->with('images')
            ->get(['id', 'name', 'category', 'price', 'type', 'stock']);

        return response()->json(['data' => $products->map(fn (Product $product): array => $this->withImages($product))->values()]);
    }
}
