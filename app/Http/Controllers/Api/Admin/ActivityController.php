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
            'search' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', 'in:all,admin,kasir,kasir_makanan,checkin,guest'],
            'event' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:120'],
            'ip' => ['nullable', 'string', 'max:45'],
            'user_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
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

        if (! empty($validated['event'])) {
            $query->where('event', 'like', '%'.$validated['event'].'%');
        }
        if (! empty($validated['ip'])) {
            $query->where('ip', 'like', '%'.$validated['ip'].'%');
        }
        if (! empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }
        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }
        if (! empty($validated['subject'])) {
            $subject = $validated['subject'];
            $query->where(function ($q) use ($subject): void {
                $q->where('subject_label', 'like', '%'.$subject.'%')
                    ->orWhere('subject_type', 'like', '%'.$subject.'%')
                    ->orWhere('subject_id', $subject);
            });
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('user_name', 'like', '%'.$search.'%')
                    ->orWhere('ip', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%')
                    ->orWhere('route', 'like', '%'.$search.'%')
                    ->orWhere('method', 'like', '%'.$search.'%')
                    ->orWhere('event', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('subject_label', 'like', '%'.$search.'%')
                    ->orWhere('user_agent', 'like', '%'.$search.'%');
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
                'event' => $log->event,
                'description' => $log->description,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'subject_label' => $log->subject_label,
                'request_id' => $log->request_id,
                'ip' => $log->ip,
                'method' => $log->method,
                'path' => $log->path,
                'route' => $log->route,
                'status' => $log->status,
                'properties' => $log->properties,
                'file_name' => $log->file_name,
                'file_mime' => $log->file_mime,
                'file_size' => $log->file_size,
                'file_path' => $log->file_path,
                'file_hash' => $log->file_hash,
                'has_file' => (bool) ($log->file_path || $log->file_name),
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
                'accounts_today' => ActivityLog::query()->whereNotNull('user_id')->whereDate('created_at', today())->distinct()->count('user_id'),
                'unique_ips_today' => ActivityLog::query()->whereDate('created_at', today())->distinct()->count('ip'),
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
        return (int) Setting::get('activity_retention_days', '365');
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
