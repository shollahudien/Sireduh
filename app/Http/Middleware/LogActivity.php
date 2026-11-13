<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records EVERY request that touches the application into activity_logs.
 * Runs at the END of the web/api groups (after session is loaded).
 * Static assets and AJAX polling endpoints are skipped to avoid flooding the table.
 */
class LogActivity
{
    /**
     * Path prefixes that should never be logged (assets, internal polls, probes).
     *
     * @var array<int, string>
     */
    protected array $skipPrefixes = [
        'build/', 'storage/', 'vendor/', 'hot', 'favicon.ico', 'robots.txt',
        '.well-known', 'up', '_boost', '_debugbar', 'livewire',
    ];

    /**
     * Exact paths/routes that are skipped (high-frequency polling/heartbeats).
     *
     * @var array<int, string>
     */
    protected array $skipExact = [
        'api/admin/notifications',
        'api/admin/logs',
        'api/admin/logs/view',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldSkip($request)) {
            return $response;
        }

        try {
            $user = $request->user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'ip' => (string) $request->ip(),
                'method' => $request->method(),
                'path' => Str::limit($request->path(), 500),
                'route' => optional($request->route())->getName(),
                'status' => $response->getStatusCode(),
                'user_agent' => Str::limit($request->userAgent() ?? '', 255),
            ]);
        } catch (Throwable) {
            // Activity logging must never break the application.
        }

        return $response;
    }

    protected function shouldSkip(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach ($this->skipPrefixes as $prefix) {
            if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return in_array($path, $this->skipExact, true);
    }
}
