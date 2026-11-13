<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks EVERYONE except the admin when maintenance mode is enabled.
 * Runs at the END of the web/api middleware groups (after session).
 */
class EnsureNotUnderMaintenance
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Setting::maintenanceEnabled()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && $user->role === 'admin') {
            return $next($request);
        }

        // Admin boleh membuka halaman login agar bisa masuk
        if ($request->is('login')) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'status' => 'maintenance',
                'message' => Setting::maintenanceMessage(),
            ], 503);
        }

        return response()->view('maintenance', [
            'message' => Setting::maintenanceMessage(),
        ], 503);
    }
}
