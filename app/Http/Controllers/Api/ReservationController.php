<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationAddon;
use App\Models\Unit;
use App\Models\Visitor;
use App\Rules\WhatsAppNumber;
use App\Services\AuditLogger;
use App\Services\AulaPackageService;
use App\Services\AvailabilityService;
use App\Services\GlampingPackageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(protected AvailabilityService $availability)
    {
        //
    }

    public function store(Request $request): JsonResponse
    {
        // Tanggal bisnis menggunakan waktu Indonesia (Asia/Jakarta) agar tamu
        // tidak bisa memesan tanggal yang sudah lewat menurut waktu venue.
        $today = now('Asia/Jakarta')->toDateString();

        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_whatsapp' => ['required', 'string', 'max:30', new WhatsAppNumber],
            'visitor_email' => ['nullable', 'email', 'max:255'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'package_id' => ['nullable', 'string', 'max:50'],
            'check_in_date' => ['required', 'date', 'after_or_equal:'.$today],
            'check_out_date' => ['nullable', 'date', 'after_or_equal:check_in_date'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'addons' => ['nullable', 'array', 'max:50'],
            // total_price sengaja diabaikan; dihitung ulang dari harga DB
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'visitor_whatsapp.required' => 'Nomor WhatsApp wajib diisi (contoh: 081234567890).',
            'check_in_date.after_or_equal' => 'Tanggal kunjungan tidak boleh sebelum hari ini.',
        ]);

        // Nomor WhatsApp dinormalisasi ke format internasional 628xxxxxxxxx
        // agar e-tiket WhatsApp terkirim dan pengunjung tidak terdupe (08… vs 62…).
        $whatsapp = WhatsAppNumber::normalize($validated['visitor_whatsapp']);

        // Anti-spam: tolak duplikasi pesanan identik/pending dalam waktu singkat dari nomor yang sama.
        $recentDuplicate = Reservation::query()
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->whereHas('visitor', fn ($q) => $q->where('whatsapp_number', $whatsapp))
            ->when(($validated['unit_id'] ?? null) !== null, fn ($q) => $q->where('unit_id', $validated['unit_id']))
            ->when(($validated['unit_id'] ?? null) === null, fn ($q) => $q->whereNull('unit_id'))
            ->whereDate('check_in_date', $validated['check_in_date'])
            ->exists();
        if ($recentDuplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan serupa baru saja dibuat dan masih menunggu pembayaran. Cek halaman pembayaran atau tunggu beberapa menit sebelum memesan lagi.',
            ], 429);
        }

        $unitId = $validated['unit_id'] ?? null;
        $package = null;
        $unitForPackage = null;

        if ($unitId !== null) {
            $unitForPackage = Unit::query()->find($unitId);
            if ($unitForPackage && $unitForPackage->type === 'glamping') {
                $package = GlampingPackageService::find($validated['package_id'] ?? null);
                if ($package === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pilih paket glamping yang tersedia.',
                    ], 422);
                }
            } elseif ($unitForPackage && $unitForPackage->type === 'aula') {
                $package = AulaPackageService::find($validated['package_id'] ?? null);
                if ($package === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pilih paket aula yang tersedia.',
                    ], 422);
                }
            }
        }

        $nowJakarta = Carbon::now('Asia/Jakarta');
        $checkInDate = Carbon::parse($validated['check_in_date'], 'Asia/Jakarta')->startOfDay();

        // Paket (glamping/aula): jam mulai otomatis. Hari ini = saat pemesanan/cek-in; hari lain = 08:00.
        if ($package !== null) {
            $inTime = $checkInDate->isSameDay($nowJakarta) ? $nowJakarta->format('H:i') : '08:00';
            $checkIn = Carbon::parse($checkInDate->toDateString().' '.$inTime, 'Asia/Jakarta');
            $checkOut = $checkIn->copy()->addHours((int) $package['duration_hours']);
            $outTime = $checkOut->format('H:i');
            $checkOutDate = $checkOut->copy()->startOfDay();
        } else {
            $inTime = $validated['check_in_time'] ?? ($unitId !== null ? '14:00' : '00:00');
            $checkIn = Carbon::parse($checkInDate->toDateString().' '.$inTime, 'Asia/Jakarta');
            $outTime = $validated['check_out_time'] ?? ($unitId !== null ? '12:00' : '23:59');

            if ($unitId !== null && empty($validated['check_out_date'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'check_out_date wajib diisi untuk reservasi unit.',
                ], 422);
            }

            $checkOutDate = $unitId !== null
                ? Carbon::parse($validated['check_out_date'], 'Asia/Jakarta')->startOfDay()
                : $checkInDate->copy();

            $checkOut = Carbon::parse($checkOutDate->toDateString().' '.$outTime, 'Asia/Jakarta');
        }

        if ($checkOut->lte($checkIn)) {
            return response()->json([
                'success' => false,
                'message' => 'Waktu check-out harus setelah waktu check-in.',
            ], 422);
        }

        $nights = ($unitId !== null && $package === null) ? max(1, (int) $checkInDate->diffInDays($checkOutDate)) : 1;

        // Normalisasi addons: menerima [product_id, ...] (quantity 1, format lama)
        // maupun [{product_id, quantity}, ...] (format baru dari frontend).
        $addonItems = [];
        foreach ($validated['addons'] ?? [] as $key => $value) {
            if (is_array($value)) {
                $productId = (int) ($value['product_id'] ?? 0);
                $quantity = (int) ($value['quantity'] ?? 1);
            } else {
                $productId = (int) $value;
                $quantity = 1;
            }

            if ($productId <= 0 || $quantity < 1 || $quantity > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tambahan tidak valid. Jumlah maksimal 100 per produk.',
                ], 422);
            }

            $addonItems[] = ['product_id' => $productId, 'quantity' => $quantity];
        }

        // Pesanan wajib memuat minimal satu unit atau satu produk tambahan.
        if ($unitId === null && $addonItems === []) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu unit atau produk tambahan.',
            ], 422);
        }

        // Kunci produk secara konsisten (urut id) untuk menghindari deadlock
        // saat dua pesanan bersamaan mengunci produk yang sama.
        usort($addonItems, fn (array $a, array $b): int => $a['product_id'] <=> $b['product_id']);

        try {
            $reservation = DB::transaction(function () use ($validated, $whatsapp, $unitId, $package, $checkIn, $checkOut, $addonItems, $nights): Reservation {
                $unit = null;
                if ($unitId !== null) {
                    if ($package !== null) {
                        // Paket glamping/aula: alokasikan slot kosong dari unit bertipe sama (glamping 6 tenda, aula sesuai kapasitas).
                        $type = Unit::query()->whereKey($unitId)->value('type');
                        $candidateIds = $type
                            ? Unit::query()->where('type', $type)->orderBy('id')->pluck('id')->all()
                            : [];
                        foreach ($candidateIds as $candidateId) {
                            try {
                                $unit = $this->availability->lockUnitForStay((int) $candidateId, $checkIn, $checkOut);
                                break;
                            } catch (RuntimeException) {
                                continue;
                            }
                        }
                        if ($unit === null) {
                            throw new RuntimeException($type === 'aula'
                                ? 'Aula sedang digunakan/penuh pada jadwal ini.'
                                : 'Semua tenda glamping sedang terisi/penuh pada jam ini. Coba lagi nanti atau pilih tanggal lain.');
                        }
                    } else {
                        $unit = $this->availability->lockUnitForStay($unitId, $checkIn, $checkOut);
                    }
                }

                // Hitung ulang total_price dari harga DB/config (anti price manipulation)
                $total = 0.0;
                if ($unit !== null) {
                    $total += $package !== null
                        ? (float) $package['price']
                        : (float) $unit->price * $nights;
                }

                $visitor = Visitor::updateOrCreate(
                    ['whatsapp_number' => $whatsapp],
                    [
                        'name' => $validated['visitor_name'],
                        'email' => $validated['visitor_email'] ?? null,
                    ],
                );

                do {
                    $orderCode = 'SIRES-'.Str::upper(Str::random(8));
                } while (Reservation::query()->where('order_code', $orderCode)->exists());

                $reservation = Reservation::create([
                    'order_code' => $orderCode,
                    'visitor_id' => $visitor->id,
                    'unit_id' => $unit?->id,
                    'package_id' => $package['id'] ?? null,
                    'package_name' => $package['name'] ?? null,
                    'package_duration_hours' => $package['duration_hours'] ?? null,
                    'check_in_date' => $checkIn->toDateString(),
                    'check_in_time' => $checkIn->format('H:i:s'),
                    'check_out_date' => $checkOut->toDateString(),
                    'check_out_time' => $checkOut->format('H:i:s'),
                    'total_price' => $total,
                    'status' => 'pending',
                ]);

                foreach ($addonItems as $addon) {
                    $product = Product::query()
                        ->whereKey($addon['product_id'])
                        ->where('type', 'addon')
                        ->lockForUpdate()
                        ->first();

                    if ($product === null) {
                        // Dilempar agar tidak ada item yang hilang diam-diam dari pesanan
                        throw new RuntimeException('Produk tambahan tidak ditemukan atau sudah dihapus.');
                    }

                    if ($product->stock < $addon['quantity']) {
                        throw new RuntimeException('Stok "'.$product->name.'" tidak mencukupi. Sisa stok: '.$product->stock.'.');
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

            Log::info('Reservasi dibuat', [
                'reservation_id' => $reservation->id,
                'order_code' => $reservation->order_code,
                'unit_id' => $reservation->unit_id,
                'status' => $reservation->status,
                'total_price' => $reservation->total_price,
                'check_in' => $reservation->checkInAt()?->toDateTimeString(),
                'check_out' => $reservation->checkOutAt()?->toDateTimeString(),
                'source' => 'online',
            ]);

            AuditLogger::log(
                'reservation.created',
                'Pesanan dibuat: '.$reservation->order_code,
                $reservation,
                [
                    'order_code' => $reservation->order_code,
                    'status' => $reservation->status,
                    'visitor' => [
                        'name' => $validated['visitor_name'],
                        'whatsapp' => $whatsapp,
                        'email' => $validated['visitor_email'] ?? null,
                    ],
                    'unit_id' => $reservation->unit_id,
                    'package' => [
                        'id' => $reservation->package_id,
                        'name' => $reservation->package_name,
                        'duration_hours' => $reservation->package_duration_hours,
                    ],
                    'check_in' => $reservation->checkInAt()?->toDateTimeString(),
                    'check_out' => $reservation->checkOutAt()?->toDateTimeString(),
                    'addons' => $addonItems,
                    'total_price' => (float) $reservation->total_price,
                    'source' => 'online',
                ],
                $request,
                null,
                $reservation->order_code
            );

            return response()->json([
                'success' => true,
                'reservation_id' => $reservation->id,
                'order_code' => $reservation->order_code,
                'status' => $reservation->status,
                'total_price' => (float) $reservation->total_price,
                'message' => 'Reservasi berhasil dibuat. Silakan lanjutkan pembayaran.',
            ], 201);
        } catch (RuntimeException $e) {
            // Error domain yang sengaja dilempar (stok kurang, unit penuh, produk hilang)
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak ditemukan.',
            ], 422);
        } catch (QueryException $e) {
            // Jangan tampilkan pesan query ke klien (bisa bocorkan struktur DB)
            Log::warning('Reservasi gagal disimpan (query)', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan reservasi. Silakan coba lagi.',
            ], 409);
        } catch (Throwable $e) {
            Log::error('Reservasi gagal diproses', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat pesanan. Silakan coba lagi.',
            ], 500);
        }
    }
}
