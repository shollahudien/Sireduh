<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\UnitStockRequest;
use App\Http\Requests\Api\Admin\UnitStoreRequest;
use App\Http\Requests\Api\Admin\UnitUpdateRequest;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use PresentsImages;

    public function index(Request $request): JsonResponse
    {
        $query = Unit::query();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $units = $query->orderBy('name')->with('images')->get();

        return response()->json([
            'data' => $units->map(fn (Unit $unit): array => $this->withImages($unit))->values(),
        ]);
    }

    public function store(UnitStoreRequest $request): JsonResponse
    {
        $unit = Unit::create($request->validated());
        $unit->load('images');

        return response()->json([
            'message' => 'Unit created successfully',
            'data' => $this->withImages($unit),
        ], 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        $unit->load('images');

        return response()->json([
            'data' => $this->withImages($unit),
        ]);
    }

    public function update(UnitUpdateRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return response()->json([
            'message' => 'Unit updated successfully',
            'data' => $this->withImages($unit->fresh('images')),
        ]);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();

        return response()->json([
            'message' => 'Unit deleted successfully',
        ]);
    }

    public function stock(UnitStockRequest $request, Unit $unit): JsonResponse
    {
        $unit->update(['stock' => $request->validated('stock')]);

        return response()->json([
            'message' => 'Unit stock updated successfully',
            'data' => $this->withImages($unit->fresh('images')),
        ]);
    }
}
