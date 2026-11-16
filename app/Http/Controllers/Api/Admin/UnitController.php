<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\PresentsImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\UnitStockRequest;
use App\Http\Requests\Api\Admin\UnitStoreRequest;
use App\Http\Requests\Api\Admin\UnitUpdateRequest;
use App\Models\Unit;
use App\Services\AuditLogger;
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

        $units = $query->orderBy('name')->with(['images', 'reservations'])->withCount(['reservations', 'posTransactionDetails'])->get();

        return response()->json([
            'data' => $units->map(fn (Unit $unit): array => $this->withImages($unit))->values(),
        ]);
    }

    public function store(UnitStoreRequest $request): JsonResponse
    {
        $unit = Unit::create($request->validated());
        $unit->load('images');

        AuditLogger::log('unit.created', 'Unit dibuat: '.$unit->name, $unit, [
            'unit' => $unit->only(['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']),
        ], $request, null, $unit->name);

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
        $before = $unit->only(['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']);
        $unit->update($request->validated());

        AuditLogger::log('unit.updated', 'Unit diperbarui: '.$unit->name, $unit, [
            'before' => $before,
            'after' => $unit->fresh()->only(['id', 'name', 'type', 'price', 'capacity', 'stock', 'description']),
        ], $request, null, $unit->name);

        return response()->json([
            'message' => 'Unit updated successfully',
            'data' => $this->withImages($unit->fresh('images')),
        ]);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        // Unit yang pernah disewa/dipakai transaksi tidak boleh dihapus
        // agar riwayat reservasi & laporan tetap utuh (FK restrictOnDelete).
        if ($unit->reservations()->exists() || $unit->posTransactionDetails()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unit ini punya riwayat sewa/transaksi dan tidak dapat dihapus. Gunakan Edit untuk mengubah detailnya.',
            ], 409);
        }

        $snapshot = $unit->only(['id', 'name', 'type', 'price', 'capacity', 'stock']);
        $unit->delete();

        AuditLogger::log('unit.deleted', 'Unit dihapus: '.$snapshot['name'], null, [
            'deleted_unit' => $snapshot,
        ], request(), null, $snapshot['name']);

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully',
        ]);
    }

    public function stock(UnitStockRequest $request, Unit $unit): JsonResponse
    {
        $before = (int) $unit->stock;
        $unit->update(['stock' => $request->validated('stock')]);

        AuditLogger::log('unit.stock_updated', 'Stok unit diperbarui: '.$unit->name, $unit, [
            'unit_id' => $unit->id,
            'name' => $unit->name,
            'stock_from' => $before,
            'stock_to' => (int) $unit->stock,
        ], $request, null, $unit->name);

        return response()->json([
            'message' => 'Unit stock updated successfully',
            'data' => $this->withImages($unit->fresh('images')),
        ]);
    }
}
