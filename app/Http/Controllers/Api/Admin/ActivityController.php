<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Paginated activity feed (search by user/IP/path, filter by role).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:all,admin,kasir,checkin,guest'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = ActivityLog::query();

        if (($validated['role'] ?? 'all') !== 'all') {
            if ($validated['role'] === 'guest') {
                $query->whereNull('user_id');
            } else {
                $query->where('user_role', $validated['role']);
            }
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('user_name', 'like', '%'.$search.'%')
                    ->orWhere('ip', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%');
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 50);
        $page = (int) ($validated['page'] ?? 1);

        $paginator = $query->latest('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (ActivityLog $log): array => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name,
                'user_role' => $log->user_role,
                'ip' => $log->ip,
                'method' => $log->method,
                'path' => $log->path,
                'route' => $log->route,
                'status' => $log->status,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->toIso8601String(),
                'created_at_human' => $log->created_at?->diffForHumans(),
            ])->values(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'today' => ActivityLog::query()->whereDate('created_at', today())->count(),
                'guests_today' => ActivityLog::query()->whereNull('user_id')->whereDate('created_at', today())->count(),
                'retention_days' => self::retentionDays(),
            ],
        ]);
    }

    /**
     * Ubah retensi hapus otomatis (3 hari / 1 minggu / 1 bulan / 1 tahun / nonaktif).
     */
    public function updateRetention(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'in:0,3,7,30,365'],
        ]);

        $days = (int) $validated['days'];

        Setting::set('activity_retention_days', (string) $days);

        $deleted = $days > 0 ? ActivityLog::pruneOlderThan($days) : 0;

        return response()->json([
            'success' => true,
            'retention_days' => $days,
            'deleted' => $deleted,
            'message' => $days > 0
                ? "Retensi diatur: lebih tua dari {$days} hari dihapus otomatis ({$deleted} entri dibersihkan sekarang)."
                : 'Hapus otomatis dimatikan.',
        ]);
    }

    public static function retentionDays(): int
    {
        return (int) Setting::get('activity_retention_days', '30');
    }

    /**
     * Delete ALL activity rows (admin discretion).
     */
    public function clear(): JsonResponse
    {
        $deleted = ActivityLog::query()->delete();

        return response()->json([
            'success' => true,
            'message' => "Riwayat aktivitas dihapus ({$deleted} entri).",
        ]);
    }
}
