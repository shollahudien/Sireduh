<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\AvailabilityService;
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
            'check_out' => ['required', 'date', 'after:check_in'],
            'type' => ['nullable', 'in:glamping,aula,alat_wisata'],
        ]);

        $checkIn = Carbon::parse($validated['check_in'])->startOfDay();
        $checkOut = Carbon::parse($validated['check_out'])->startOfDay();

        $units = $this->availability->availableUnits(
            $checkIn,
            $checkOut,
            $validated['type'] ?? null
        );

        $units->load('images');

        return response()->json([
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'data' => $units->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'name' => $unit->name,
                'type' => $unit->type,
                'price' => $unit->price,
                'capacity' => $unit->capacity,
                'stock' => $unit->stock,
                'images' => $this->presentImages($unit),
            ])->values(),
        ]);
    }
}
