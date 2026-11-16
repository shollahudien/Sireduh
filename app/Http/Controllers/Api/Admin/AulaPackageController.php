<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AulaPackage;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AulaPackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = AulaPackage::query()->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['data' => $packages->map(fn (AulaPackage $p): array => [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'duration_hours' => (int) $p->duration_hours,
            'price' => (float) $p->price,
            'is_active' => (bool) $p->is_active,
            'sort_order' => (int) $p->sort_order,
        ])->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:aula_packages,code'],
            'name' => ['required', 'string', 'max:255'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $package = AulaPackage::create($validated);

        AuditLogger::log('aula_package.created', 'Paket aula dibuat: '.$package->name, $package, [
            'package' => $package->only(['id', 'code', 'name', 'duration_hours', 'price', 'is_active', 'sort_order']),
        ], $request, null, $package->name);

        return response()->json(['message' => 'Paket aula dibuat.', 'data' => $package], 201);
    }

    public function update(Request $request, AulaPackage $aulaPackage): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $before = $aulaPackage->only(['id', 'code', 'name', 'duration_hours', 'price', 'is_active', 'sort_order']);
        $aulaPackage->update($validated);

        AuditLogger::log('aula_package.updated', 'Paket aula diperbarui: '.$aulaPackage->name, $aulaPackage, [
            'before' => $before,
            'after' => $aulaPackage->fresh()->only(['id', 'code', 'name', 'duration_hours', 'price', 'is_active', 'sort_order']),
        ], $request, null, $aulaPackage->name);

        return response()->json(['message' => 'Paket aula diperbarui.', 'data' => $aulaPackage->fresh()]);
    }

    public function destroy(AulaPackage $aulaPackage): JsonResponse
    {
        $snapshot = $aulaPackage->only(['id', 'code', 'name', 'duration_hours', 'price']);
        $aulaPackage->delete();

        AuditLogger::log('aula_package.deleted', 'Paket aula dihapus: '.$snapshot['name'], null, [
            'deleted_package' => $snapshot,
        ], request(), null, $snapshot['name']);

        return response()->json(['success' => true, 'message' => 'Paket aula dihapus.']);
    }
}
