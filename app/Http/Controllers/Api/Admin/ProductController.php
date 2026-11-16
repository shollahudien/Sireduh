<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ProductStockRequest;
use App\Http\Requests\Api\Admin\ProductStoreRequest;
use App\Http\Requests\Api\Admin\ProductUpdateRequest;
use App\Models\Product;
use App\Services\AuditLogger;
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

        $products = $query->orderBy('name')->with(['images', 'addons.product'])->withCount(['addons', 'posDetails'])->get();

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

        AuditLogger::log('product.created', 'Produk dibuat: '.$product->name, $product, [
            'product' => $product->only(['id', 'name', 'category', 'price', 'type', 'stock']),
        ], $request, null, $product->name);

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
        $before = $product->only(['id', 'name', 'category', 'price', 'type', 'stock', 'description', 'for_kasir_makanan']);
        $product->update($request->validated());

        AuditLogger::log('product.updated', 'Produk diperbarui: '.$product->name, $product, [
            'before' => $before,
            'after' => $product->fresh()->only(['id', 'name', 'category', 'price', 'type', 'stock', 'description', 'for_kasir_makanan']),
        ], $request, null, $product->name);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $this->withImages($product->fresh('images')),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        // Produk yang sudah tercatat di reservasi/POS tidak boleh dihapus
        // agar riwayat transaksi dan laporan tetap utuh (FK restrictOnDelete).
        if ($product->addons()->exists() || $product->posDetails()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk ini punya riwayat transaksi dan tidak dapat dihapus. Gunakan Edit untuk mengubah nama, harga, atau stoknya.',
            ], 409);
        }

        $snapshot = $product->only(['id', 'name', 'category', 'price', 'type', 'stock']);
        $product->delete();

        AuditLogger::log('product.deleted', 'Produk dihapus: '.$snapshot['name'], null, [
            'deleted_product' => $snapshot,
        ], request(), null, $snapshot['name']);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function stock(ProductStockRequest $request, Product $product): JsonResponse
    {
        $before = (int) $product->stock;
        $product->update(['stock' => $request->validated('stock')]);

        AuditLogger::log('product.stock_updated', 'Stok produk diperbarui: '.$product->name, $product, [
            'product_id' => $product->id,
            'name' => $product->name,
            'stock_from' => $before,
            'stock_to' => (int) $product->stock,
        ], $request, null, $product->name);

        return response()->json([
            'message' => 'Product stock updated successfully',
            'data' => $this->withImages($product->fresh('images')),
        ]);
    }

    public function exportCsv()
    {
        $products = Product::with(['addons.product', 'posDetails.product'])->get();
        $rows = [];

        foreach ($products as $product) {
            $rows[] = [
                $product->name,
                $product->category,
                $product->price,
                $product->type ?? 'pos',
                $product->stock ?? 0,
            ];
        }

        $callback = function () use ($rows) {
            $file = fopen('php://memory', 'w');
            fputcsv($file, ['Nama', 'Kategori', 'Harga', 'Tipe', 'Stok']);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            rewind($file);

            return $file;
        };

        $filename = 'products-'.now()->format('Ymd').'.csv';

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
