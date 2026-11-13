<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\PosTransactionDetail;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PosController extends Controller
{
    use PresentsImages;

    /**
     * Area check-in — hanya boleh menjual tiket masuk.
     */
    protected function checkin(): bool
    {
        return request()->user()?->role === 'checkin';
    }

    /**
     * Kasir Makanan hanya boleh menjual produk yang diizinkan admin (for_kasir_makanan).
     */
    protected function foodOnly(): bool
    {
        return request()->user()?->role === 'kasir_makanan';
    }

    public function products(): JsonResponse
    {
        $query = Product::query()
            ->where('type', 'pos')
            ->when($this->foodOnly(), fn ($q) => $q->where('for_kasir_makanan', true))
            ->when($this->checkin(), fn ($q) => $q->where('category', 'tiket_masuk'))
            ->orderBy('name')
            ->with('images');

        $products = $query->get();

        return response()->json([
            'data' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'price' => $product->price,
                'type' => $product->type,
                'stock' => $product->stock,
                'images' => $this->presentImages($product),
            ])->values(),
        ]);
    }

    /**
     * Riwayat penjualan milik kasir yang sedang login,
     * dengan ringkasan total per metode pembayaran.
     */
    public function riwayat(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? now()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        $transactions = PosTransaction::query()
            ->with(['user:id,name', 'details.product:id,name', 'details.unit:id,name'])
            ->where('user_id', $user->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->latest()
            ->limit(200)
            ->get();

        $summary = [
            'cash' => ['count' => 0, 'total' => 0.0],
            'qris' => ['count' => 0, 'total' => 0.0],
            'transfer' => ['count' => 0, 'total' => 0.0],
        ];

        foreach ($transactions as $transaction) {
            $method = $transaction->payment_method;

            if (! isset($summary[$method])) {
                $summary[$method] = ['count' => 0, 'total' => 0.0];
            }

            $summary[$method]['count']++;
            $summary[$method]['total'] += (float) $transaction->total_amount;
        }

        $cashTotal = $summary['cash']['total'];

        // Ringkasan item terjual (agregat qty per produk/unit)
        $items = $transactions->flatMap(fn (PosTransaction $t) => $t->details)
            ->groupBy(fn ($d) => $d->product_id ?? 'unit-'.$d->unit_id)
            ->map(function ($group) {
                $first = $group->first();
                $name = $first->product?->name ?? $first->unit?->name;
                $qty = $group->sum('quantity');
                $subtotal = $group->sum('subtotal');

                return [
                    'name' => $name,
                    'qty' => $qty,
                    'subtotal' => (float) $subtotal,
                ];
            })
            ->sortByDesc('subtotal')
            ->values()
            ->all();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'kasir' => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'summary' => [
                'cash' => ['count' => $summary['cash']['count'], 'total' => $cashTotal],
                'qris' => ['count' => $summary['qris']['count'], 'total' => (float) $summary['qris']['total']],
                'transfer' => ['count' => $summary['transfer']['count'], 'total' => (float) $summary['transfer']['total']],
                'total_transactions' => $transactions->count(),
                'total_amount' => (float) $transactions->sum('total_amount'),
            ],
            'transactions' => $transactions->map(fn (PosTransaction $t): array => [
                'id' => $t->id,
                'created_at' => $t->created_at?->toIso8601String(),
                'payment_method' => $t->payment_method,
                'total_amount' => (float) $t->total_amount,
                'items' => $t->details->map(fn ($d): array => [
                    'name' => $d->product?->name ?? $d->unit?->name,
                    'quantity' => $d->quantity,
                    'subtotal' => (float) $d->subtotal,
                    'kind' => $d->product_id ? 'produk' : 'sewa',
                ])->values(),
            ])->values(),
            'items_sold' => $items,
        ]);
    }

    public function units(): JsonResponse
    {
        // Check-in: hanya jual tiket masuk. Kasir Makanan: produk yang diizinkan admin.
        if ($this->checkin() || $this->foodOnly()) {
            return response()->json(['data' => []]);
        }

        $units = Unit::query()
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->with('images')
            ->get(['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']);

        return response()->json(['data' => $units->map(fn (Unit $unit): array => array_merge($unit->toArray(), ['images' => $this->presentImages($unit)]))->values()]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($this->checkin() && ! in_array($validated['payment_method'], ['cash', 'qris'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in hanya menerima pembayaran Tunai (cash) atau QRIS.',
            ], 422);
        }

        $user = $request->user();

        try {
            $transaction = DB::transaction(function () use ($validated, $user) {
                $total = 0.0;

                $transaction = PosTransaction::create([
                    'user_id' => $user->id,
                    'total_amount' => 0,
                    'payment_method' => $validated['payment_method'],
                ]);

                foreach ($validated['items'] as $item) {
                    $qty = (int) $item['quantity'];
                    $productId = $item['product_id'] ?? null;
                    $unitId = $item['unit_id'] ?? null;

                    if ($productId === null && $unitId === null) {
                        throw new \RuntimeException('Item checkout tidak valid: product_id atau unit_id harus diisi.');
                    }

                    if ($productId !== null) {
                        $detail = $this->checkoutProduct($transaction, $productId, $qty);
                        $total += $detail['subtotal'];
                    } else {
                        $detail = $this->checkoutUnit($transaction, $unitId, $qty);
                        $total += $detail['subtotal'];
                    }
                }

                $transaction->update(['total_amount' => $total]);

                return $transaction->load(['details.product', 'details.unit']);
            });

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'user_id' => $transaction->user_id,
                    'total_amount' => $transaction->total_amount,
                    'payment_method' => $transaction->payment_method,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                    'details' => $transaction->details->map(fn (PosTransactionDetail $d): array => [
                        'product_id' => $d->product_id,
                        'unit_id' => $d->unit_id,
                        'name' => $d->product?->name ?? $d->unit?->name,
                        'quantity' => $d->quantity,
                        'subtotal' => $d->subtotal,
                    ])->values(),
                ],
                'message' => 'Transaksi POS berhasil diproses.',
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array{subtotal: float}
     */
    protected function checkoutProduct(PosTransaction $transaction, int $productId, int $qty): array
    {
        /** @var Product|null $product */
        $product = Product::query()
            ->whereKey($productId)
            ->where('type', 'pos')
            ->when($this->foodOnly(), fn ($q) => $q->where('for_kasir_makanan', true))
            ->when($this->checkin(), fn ($q) => $q->where('category', 'tiket_masuk'))
            ->lockForUpdate()
            ->first();

        if ($product === null) {
            $reason = $this->checkin()
                ? 'Hanya tiket masuk yang bisa dijual dari area check-in.'
                : ($this->foodOnly()
                    ? 'Produk ini tidak diizinkan untuk Kasir Makanan. Admin bisa mengaktifkannya di menu Produk.'
                    : 'Produk POS tidak ditemukan.');

            throw new \RuntimeException($reason);
        }

        if ($product->stock < $qty) {
            throw new \RuntimeException("Stok {$product->name} tidak mencukupi.");
        }

        $subtotal = (float) $product->price * $qty;

        PosTransactionDetail::create([
            'pos_transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'subtotal' => $subtotal,
        ]);

        $product->decrement('stock', $qty);

        return ['subtotal' => $subtotal];
    }

    /**
     * @return array{subtotal: float}
     */
    protected function checkoutUnit(PosTransaction $transaction, int $unitId, int $qty): array
    {
        /** @var Unit|null $unit */
        if ($this->checkin()) {
            throw new \RuntimeException('Area check-in hanya boleh menjual tiket masuk.');
        }

        if ($this->foodOnly()) {
            throw new \RuntimeException('Kasir Makanan tidak boleh menyewa unit.');
        }

        $unit = Unit::query()
            ->whereKey($unitId)
            ->lockForUpdate()
            ->first();

        if ($unit === null) {
            throw new \RuntimeException('Unit tidak ditemukan.');
        }

        if ($unit->stock < $qty) {
            throw new \RuntimeException("Stok {$unit->name} tidak mencukupi.");
        }

        $subtotal = (float) $unit->price * $qty;

        PosTransactionDetail::create([
            'pos_transaction_id' => $transaction->id,
            'unit_id' => $unit->id,
            'quantity' => $qty,
            'subtotal' => $subtotal,
        ]);

        $unit->decrement('stock', $qty);

        return ['subtotal' => $subtotal];
    }
}
