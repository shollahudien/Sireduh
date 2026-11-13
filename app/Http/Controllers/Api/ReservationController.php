<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationAddon;
use App\Models\Unit;
use App\Models\Visitor;
use App\Rules\WhatsAppNumber;
use App\Services\AvailabilityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(protected AvailabilityService $availability)
    {
        //
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_whatsapp' => ['required', 'string', 'max:30', new WhatsAppNumber],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['nullable', 'date', 'after:check_in_date'],
            'addons' => ['nullable', 'array'],
            // total_price sengaja diabaikan; dihitung ulang dari harga DB
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'visitor_whatsapp.required' => 'Nomor WhatsApp wajib diisi (contoh: 081234567890).',
        ]);

        // Nomor WhatsApp dinormalisasi ke format internasional 628xxxxxxxxx
        // agar e-tiket WhatsApp terkirim dan pengunjung tidak terdupe (08… vs 62…).
        $whatsapp = WhatsAppNumber::normalize($validated['visitor_whatsapp']);

        $unitId = $validated['unit_id'] ?? null;
        $checkIn = Carbon::parse($validated['check_in_date'])->startOfDay();

        if ($unitId !== null && empty($validated['check_out_date'])) {
            return response()->json([
                'success' => false,
                'message' => 'check_out_date wajib diisi untuk reservasi unit.',
            ], 422);
        }

        $checkOut = $unitId !== null
            ? Carbon::parse($validated['check_out_date'])->startOfDay()
            : $checkIn->copy();

        $nights = $unitId !== null ? max(1, (int) $checkIn->diffInDays($checkOut)) : 1;

        // Normalisasi addons: menerima [product_id, ...] (quantity 1, format lama)
        // maupun [{product_id, quantity}, ...] (format baru dari frontend).
        $addonItems = [];
        foreach ($validated['addons'] ?? [] as $key => $value) {
            if (is_array($value)) {
                $productId = (int) ($value['product_id'] ?? 0);
                $quantity = max(1, (int) ($value['quantity'] ?? 1));
            } else {
                $productId = (int) $value;
                $quantity = 1;
            }

            if ($productId > 0) {
                $addonItems[] = ['product_id' => $productId, 'quantity' => $quantity];
            }
        }

        try {
            $reservation = DB::transaction(function () use ($validated, $whatsapp, $unitId, $checkIn, $checkOut, $addonItems, $nights): Reservation {
                $unit = $unitId !== null
                    ? $this->availability->lockUnitForStay($unitId, $checkIn, $checkOut)
                    : null;

                // Hitung ulang total_price dari harga DB (anti price manipulation)
                $total = 0.0;
                if ($unit !== null) {
                    $total += (float) $unit->price * $nights;
                }

                $visitor = Visitor::updateOrCreate(
                    ['whatsapp_number' => $whatsapp],
                    [
                        'name' => $validated['visitor_name'],
                        'email' => $validated['visitor_email'] ?? null,
                    ],
                );

                $reservation = Reservation::create([
                    'order_code' => 'SIRES-'.Str::upper(Str::random(8)),
                    'visitor_id' => $visitor->id,
                    'unit_id' => $unit?->id,
                    'check_in_date' => $checkIn->toDateString(),
                    'check_out_date' => $checkOut->toDateString(),
                    'total_price' => $total,
                    'status' => 'pending',
                ]);

                foreach ($addonItems as $addon) {
                    $product = Product::query()
                        ->whereKey($addon['product_id'])
                        ->where('type', 'addon')
                        ->lockForUpdate()
                        ->first();

                    if ($product === null || $product->stock < $addon['quantity']) {
                        continue;
                    }

                    ReservationAddon::create([
                        'reservation_id' => $reservation->id,
                        'product_id' => $product->id,
                        'quantity' => $addon['quantity'],
                        'price' => $product->price,
                    ]);

                    $product->decrement('stock', $addon['quantity']);

                    // Addon price diambil dari DB, bukan dari frontend
                    $total += (float) $product->price * $addon['quantity'];
                }

                // Update total_price final (unit + addons) — recalculation aman
                $reservation->update(['total_price' => $total]);

                return $reservation;
            });

            return response()->json([
                'success' => true,
                'reservation_id' => $reservation->id,
                'order_code' => $reservation->order_code,
                'status' => $reservation->status,
                'total_price' => (float) $reservation->total_price,
                'message' => 'Reservasi berhasil dibuat. Silakan lanjutkan pembayaran.',
            ], 201);
        } catch (QueryException|\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unit tidak tersedia pada tanggal yang dipilih.',
            ], 409);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
