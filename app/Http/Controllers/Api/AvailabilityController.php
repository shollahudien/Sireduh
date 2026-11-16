<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\AulaPackageService;
use App\Services\AvailabilityService;
use App\Services\GlampingPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    use PresentsImages;

    public function __construct(protected AvailabilityService $availability)
    {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'package_id' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'in:glamping,aula,alat_wisata'],
        ]);

        $type = $validated['type'] ?? null;
        $package = null;
        if (($type === 'aula') || ($type === null)) {
            $package = AulaPackageService::find($validated['package_id'] ?? null);
        }
        if ($package === null) {
            $package = GlampingPackageService::find($validated['package_id'] ?? null);
        }
        $nowJakarta = Carbon::now('Asia/Jakarta');
        $checkInDate = Carbon::parse($validated['check_in'], 'Asia/Jakarta');
        $isPackaged = in_array($type, ['glamping', 'aula'], true) || $package !== null;
        // Paket/hari ini: jam mulai otomatis (saat ini) agar status aktif/terisi terdeteksi.
        if ($isPackaged && $checkInDate->isSameDay($nowJakarta)) {
            $inTime = $nowJakarta->format('H:i');
        } else {
            $inTime = $validated['check_in_time'] ?? ($isPackaged ? '08:00' : '14:00');
        }
        $checkIn = Carbon::parse($checkInDate->toDateString().' '.$inTime, 'Asia/Jakarta');

        if ($package !== null) {
            $checkOut = $checkIn->copy()->addHours((int) $package['duration_hours']);
        } else {
            $outTime = $validated['check_out_time'] ?? '12:00';
            $checkOutDate = $validated['check_out'] ?? $validated['check_in'];
            $checkOut = Carbon::parse($checkOutDate.' '.$outTime);
        }

        if ($checkOut->lte($checkIn)) {
            return response()->json(['message' => 'Waktu check-out harus setelah check-in.'], 422);
        }

        $query = Unit::query()->where('stock', '>', 0)->orderBy('name');

        if (($validated['type'] ?? null) !== null) {
            $query->where('type', $validated['type']);
        }

        $units = $query->get();
        $units->load('images');
        $booked = $this->availability->bookedCountPerUnit($checkIn, $checkOut);

        $data = $units->map(function (Unit $unit) use ($package, $booked): array {
            $remaining = max(0, (int) $unit->stock - (int) ($booked[$unit->id] ?? 0));

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'type' => $unit->type,
                'price' => in_array($unit->type, ['glamping', 'aula'], true) && $package ? (float) $package['price'] : (float) $unit->price,
                'base_price' => (float) $unit->price,
                'capacity' => $unit->capacity,
                'stock' => $unit->stock,
                'remaining_slots' => $remaining,
                'available_slots' => $remaining,
                'images' => $this->presentImages($unit),
            ];
        })->values();

        $glampingSlots = (int) collect($data)->where('type', 'glamping')->sum('remaining_slots');
        $aulaSlots = (int) collect($data)->where('type', 'aula')->sum('remaining_slots');

        return response()->json([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'check_in_time' => $checkIn->format('H:i'),
            'check_out_time' => $checkOut->format('H:i'),
            'package' => $package,
            'glamping_packages' => GlampingPackageService::all(),
            'aula_packages' => AulaPackageService::all(),
            'packages' => GlampingPackageService::all(),
            'glamping_remaining_slots' => $glampingSlots,
            'aula_remaining_slots' => $aulaSlots,
            'data' => $data,
        ]);
    }
}
