<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ProductStockRequest;
use App\Http\Requests\Api\Admin\ProductStoreRequest;
use App\Http\Requests\Api\Admin\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use PresentsImages;

    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $products = $query->orderBy('name')->with('images')->get();

        return response()->json([
            'data' => $products->map(fn (Product $product): array => $this->withImages($product))->values(),
        ]);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['type'])) {
            $data['type'] = ($data['category'] === 'tiket_masuk') ? 'addon' : 'pos';
        }

        $product = Product::create($data);
        $product->load('images');

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $this->withImages($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('images');

        return response()->json([
            'data' => $this->withImages($product),
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $this->withImages($product->fresh('images')),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    public function stock(ProductStockRequest $request, Product $product): JsonResponse
    {
        $product->update(['stock' => $request->validated('stock')]);

        return response()->json([
            'message' => 'Product stock updated successfully',
            'data' => $this->withImages($product->fresh('images')),
        ]);
    }
}
