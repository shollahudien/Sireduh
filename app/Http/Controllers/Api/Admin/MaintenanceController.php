<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    /**
     * Current maintenance state (admin only).
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'enabled' => Setting::maintenanceEnabled(),
            'message' => Setting::maintenanceMessage(),
        ]);
    }

    /**
     * Toggle maintenance mode on/off (admin only).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::set('maintenance_mode', $validated['enabled'] ? '1' : '0');

        if (array_key_exists('message', $validated)) {
            Setting::set('maintenance_message', $validated['message'] !== '' ? $validated['message'] : null);
        }

        return response()->json([
            'success' => true,
            'enabled' => Setting::maintenanceEnabled(),
            'message' => Setting::maintenanceMessage(),
            'note' => 'Selama mode maintenance aktif, HANYA admin yang dapat login & mengakses aplikasi.',
        ]);
    }
}
