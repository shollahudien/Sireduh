<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlampingPackage;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlampingPackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = GlampingPackage::query()->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['data' => $packages->map(fn (GlampingPackage $p): array => [
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
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:glamping_packages,code'],
            'name' => ['required', 'string', 'max:255'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $package = GlampingPackage::create($validated);

        AuditLogger::log('glamping_package.created', 'Paket glamping dibuat: '.$package->name, $package, [
            'package' => $package->only(['id', 'code', 'name', 'duration_hours', 'price', 'is_active', 'sort_order']),
        ], $request, null, $package->name);

        return response()->json(['message' => 'Paket glamping dibuat.', 'data' => $package], 201);
    }

    public function update(Request $request, GlampingPackage $glampingPackage): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $before = $glampingPackage->only(['id', 'code', 'name', 'duration_hours', 'price', 'is_active', 'sort_order']);
        $glampingPackage->update($validated);

        AuditLogger::log('glamping_package.updated', 'Paket glamping diperbarui: '.$glampingPackage->name, $glampingPackage, [
            'before' => $before,
            'after' => $glampingPackage->fresh()->only(['id', 'code', 'name', 'duration_hours', 'price', 'is_active', 'sort_order']),
        ], $request, null, $glampingPackage->name);

        return response()->json(['message' => 'Paket glamping diperbarui.', 'data' => $glampingPackage->fresh()]);
    }

    public function destroy(GlampingPackage $glampingPackage): JsonResponse
    {
        $snapshot = $glampingPackage->only(['id', 'code', 'name', 'duration_hours', 'price']);
        $glampingPackage->delete();

        AuditLogger::log('glamping_package.deleted', 'Paket glamping dihapus: '.$snapshot['name'], null, [
            'deleted_package' => $snapshot,
        ], request(), null, $snapshot['name']);

        return response()->json(['success' => true, 'message' => 'Paket glamping dihapus.']);
    }
}
