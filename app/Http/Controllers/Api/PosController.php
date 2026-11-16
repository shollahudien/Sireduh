<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\PosTransactionDetail;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\Visitor;
use App\Services\AuditLogger;
use App\Services\AulaPackageService;
use App\Services\AvailabilityService;
use App\Services\GlampingPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PosController extends Controller
{
    use PresentsImages;

    public function __construct(protected AvailabilityService $availability)
    {
        //
    }

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
            ->orderBy('name')
            ->with('images')
            ->get(['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']);

        return response()->json([
            'data' => $units->map(fn (Unit $unit): array => array_merge($unit->toArray(), [
                'images' => $this->presentImages($unit),
            ]))->values(),
            'glamping_packages' => GlampingPackageService::all(),
            'aula_packages' => AulaPackageService::all(),
        ]);
    }

    public function glampingAvailability(Request $request): JsonResponse
    {
        return $this->packageAvailability($request, 'glamping');
    }

    public function aulaAvailability(Request $request): JsonResponse
    {
        return $this->packageAvailability($request, 'aula');
    }

    protected function packageAvailability(Request $request, string $type): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'package_id' => ['required', 'string', 'max:50'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $package = $type === 'aula'
            ? AulaPackageService::find($validated['package_id'])
            : GlampingPackageService::find($validated['package_id']);
        if ($package === null) {
            return response()->json(['message' => 'Paket tidak ditemukan.'], 422);
        }

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $checkInDate = Carbon::parse($validated['check_in'], 'Asia/Jakarta');
        // Jam otomatis: jika hari ini pakai waktu sekarang agar tenda/glamping yang masih aktif terhitung terisi.
        $inTime = $checkInDate->isSameDay($nowJakarta) ? $nowJakarta->format('H:i') : ($validated['check_in_time'] ?? '08:00');
        $checkIn = Carbon::parse($checkInDate->toDateString().' '.$inTime, 'Asia/Jakarta');
        $checkOut = $checkIn->copy()->addHours((int) $package['duration_hours']);
        $quantity = (int) ($validated['quantity'] ?? 1);

        $units = Unit::query()->where('type', $type)->get();
        $available = 0;
        foreach ($units as $unit) {
            $available += $this->availability->remainingCapacity($unit->id, $checkIn, $checkOut);
        }

        return response()->json([
            'available' => $available >= $quantity,
            'available_slots' => $available,
            'requested' => $quantity,
            'check_in' => $checkIn->toDateTimeString(),
            'check_out' => $checkOut->toDateTimeString(),
            'package' => $package,
            'type' => $type,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.package_id' => ['nullable', 'string', 'max:50'],
            'items.*.check_in_date' => ['nullable', 'date'],
            'items.*.check_in_time' => ['nullable', 'date_format:H:i'],
            'items.*.check_out_date' => ['nullable', 'date'],
            'items.*.check_out_time' => ['nullable', 'date_format:H:i'],
            'items.*.visitor_name' => ['nullable', 'string', 'max:255'],
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
                        throw new RuntimeException('Item checkout tidak valid: product_id atau unit_id harus diisi.');
                    }

                    if ($productId !== null) {
                        $detail = $this->checkoutProduct($transaction, $productId, $qty);
                        $total += $detail['subtotal'];
                    } else {
                        $detail = $this->checkoutUnit($transaction, $unitId, $qty, $item, $validated['payment_method'], $user->id);
                        $total += $detail['subtotal'];
                    }
                }

                $transaction->update(['total_amount' => $total]);

                return $transaction->load(['details.product', 'details.unit']);
            });

            \Log::info('Transaksi POS berhasil', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'payment_method' => $transaction->payment_method,
                'total_amount' => $transaction->total_amount,
                'items' => $transaction->details->count(),
            ]);

            AuditLogger::log(
                'pos.checkout',
                'Transaksi POS #'.$transaction->id.' berhasil',
                $transaction,
                [
                    'transaction_id' => $transaction->id,
                    'payment_method' => $transaction->payment_method,
                    'total_amount' => (float) $transaction->total_amount,
                    'items' => $transaction->details->map(fn ($d) => [
                        'name' => $d->product?->name ?? $d->unit?->name,
                        'product_id' => $d->product_id,
                        'unit_id' => $d->unit_id,
                        'quantity' => $d->quantity,
                        'subtotal' => (float) $d->subtotal,
                    ])->values()->all(),
                ],
                $request,
                null,
                'POS#'.$transaction->id
            );

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

            throw new RuntimeException($reason);
        }

        if ($product->stock < $qty) {
            throw new RuntimeException("Stok {$product->name} tidak mencukupi.");
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
    protected function checkoutUnit(PosTransaction $transaction, int $unitId, int $qty, array $item, string $paymentMethod, int $cashierUserId): array
    {
        if ($this->checkin()) {
            throw new RuntimeException('Area check-in hanya boleh menjual tiket masuk.');
        }

        if ($this->foodOnly()) {
            throw new RuntimeException('Kasir Makanan tidak boleh menyewa unit.');
        }

        /** @var Unit|null $unit */
        $unit = Unit::query()->whereKey($unitId)->lockForUpdate()->first();
        if ($unit === null) {
            throw new RuntimeException('Unit tidak ditemukan.');
        }

        // Glamping/Aula: booking berbasis paket & jadwal, berbagi slot kapasitas dengan online.
        if (in_array($unit->type, ['glamping', 'aula'], true)) {
            $checkInDate = $item['check_in_date'] ?? null;
            $checkInTime = $item['check_in_time'] ?? null;
            if (! $checkInDate || ! $checkInTime) {
                throw new RuntimeException('Tanggal dan jam mulai wajib diisi untuk '.($unit->type === 'aula' ? 'aula' : 'glamping').'.');
            }

            $package = $unit->type === 'aula'
                ? AulaPackageService::find($item['package_id'] ?? null)
                : GlampingPackageService::find($item['package_id'] ?? null);
            if ($package === null) {
                throw new RuntimeException('Pilih paket '.($unit->type === 'aula' ? 'aula' : 'glamping').'.');
            }

            $nowJakarta = Carbon::now('Asia/Jakarta');
            $checkInDateObj = Carbon::parse($checkInDate, 'Asia/Jakarta');
            // Jam otomatis saat pembelian: hari ini pakai waktu sekarang agar tenda aktif tidak dibooking ganda.
            $effectiveInTime = $checkInDateObj->isSameDay($nowJakarta) ? $nowJakarta->format('H:i') : $checkInTime;
            $checkIn = Carbon::parse($checkInDate.' '.$effectiveInTime, 'Asia/Jakarta');
            $checkOut = $checkIn->copy()->addHours((int) $package['duration_hours']);
            $visitorName = trim((string) ($item['visitor_name'] ?? 'Walk-in (POS)')) ?: 'Walk-in (POS)';

            for ($i = 0; $i < $qty; $i++) {
                // Cari & kunci salah satu unit tipe tsb yang masih punya slot pada jadwal tsb.
                $bookedUnit = null;
                foreach (Unit::query()->where('type', $unit->type)->lockForUpdate()->get() as $candidate) {
                    try {
                        $bookedUnit = $this->availability->lockUnitForStay($candidate->id, $checkIn, $checkOut);
                        break;
                    } catch (RuntimeException) {
                        continue;
                    }
                }

                if ($bookedUnit === null) {
                    throw new RuntimeException($unit->type === 'aula'
                        ? 'Aula sedang digunakan/penuh pada jadwal ini.'
                        : 'Semua 6 tenda glamping sedang terisi/digunakan pada jam ini.');
                }

                $visitor = Visitor::create([
                    'name' => $visitorName,
                    'whatsapp_number' => null,
                    'email' => null,
                ]);

                do {
                    $orderCode = 'POS-'.Str::upper(Str::random(8));
                } while (Reservation::query()->where('order_code', $orderCode)->exists());

                $price = (float) $package['price'];

                $reservation = Reservation::create([
                    'order_code' => $orderCode,
                    'visitor_id' => $visitor->id,
                    'unit_id' => $bookedUnit->id,
                    'package_id' => $package['id'],
                    'package_name' => $package['name'],
                    'package_duration_hours' => $package['duration_hours'],
                    'check_in_date' => $checkIn->toDateString(),
                    'check_in_time' => $checkIn->format('H:i:s'),
                    'check_out_date' => $checkOut->toDateString(),
                    'check_out_time' => $checkOut->format('H:i:s'),
                    'total_price' => $price,
                    'status' => 'confirmed',
                ]);

                $reservation->payment()->create([
                    'amount' => $price,
                    'method' => $paymentMethod,
                    'status' => 'verified',
                    'proof_image' => '',
                    'verified_by' => $cashierUserId,
                    'verified_at' => now(),
                ]);
            }

            $subtotal = (float) $package['price'] * $qty;

            PosTransactionDetail::create([
                'pos_transaction_id' => $transaction->id,
                'unit_id' => $unit->id,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ]);

            return ['subtotal' => $subtotal];
        }

        // Non-glamping/aula: perilaku lama (sewa/per item, stok berkurang).
        if ($unit->stock < $qty) {
            throw new RuntimeException("Stok {$unit->name} tidak mencukupi.");
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
