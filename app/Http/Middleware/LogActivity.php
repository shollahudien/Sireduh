<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogActivity
{
    /** @var array<int, string> */
    protected array $skipPrefixes = [
        'build/', 'storage/', 'vendor/', 'hot', 'favicon.ico', 'robots.txt',
        '.well-known', 'up', '_boost', '_debugbar', 'livewire',
    ];

    /** @var array<int, string> */
    protected array $skipExact = [
        'api/admin/notifications',
        'api/admin/logs',
        'api/admin/logs/view',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->has('audit_request_id')) {
            $request->attributes->set('audit_request_id', (string) Str::uuid());
        }

        $response = $next($request);

        if ($this->shouldSkip($request)) {
            return $response;
        }

        try {
            $user = $request->user();
            $path = trim($request->path(), '/');
            $route = $request->route();
            $subject = $this->resolveSubject($request);
            $event = $this->eventFor($request, $response, $path);
            $description = $this->descriptionFor($request, $response, $path, $user !== null);

            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'event' => $event,
                'description' => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => AuditLogger::subjectLabel($subject),
                'request_id' => $request->attributes->get('audit_request_id'),
                'ip' => (string) $request->ip(),
                'method' => $request->method(),
                'path' => Str::limit($request->path(), 500),
                'route' => $route?->getName(),
                'status' => $response->getStatusCode(),
                'properties' => $this->propertiesFor($request, $response),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable) {
        }

        return $response;
    }

    protected function resolveSubject(Request $request): ?Model
    {
        try {
            foreach (($request->route()?->parameters() ?? []) as $value) {
                if ($value instanceof Model) {
                    return $value;
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    protected function eventFor(Request $request, Response $response, string $path): string
    {
        $method = strtoupper($request->method());
        $status = $response->getStatusCode();
        $base = 'http.request';
        if ($path === 'login' && $method === 'POST') {
            $base = $status >= 400 ? 'auth.login.failed' : 'auth.login.attempt';
        } elseif ($path === 'logout') {
            $base = 'auth.logout';
        } elseif ($method !== 'GET' && $status < 400) {
            $base = 'http.mutation';
        } elseif ($status >= 400) {
            $base = 'http.error';
        }

        return $base;
    }

    protected function descriptionFor(Request $request, Response $response, string $path, bool $authed): string
    {
        $who = $authed ? 'Akun' : 'Tamu/guest';
        $method = strtoupper($request->method());
        $status = $response->getStatusCode();

        return "{$who} {$method} /{$path} (status {$status})";
    }

    /**
     * @return array<string, mixed>
     */
    protected function propertiesFor(Request $request, Response $response): array
    {
        $props = [
            'query' => AuditLogger::sanitize($request->query()),
        ];

        $contentType = (string) $request->header('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $props['input'] = AuditLogger::sanitize($request->except(self::redactInputKeys()));
        } elseif (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $props['input_keys'] = array_values(array_diff(array_keys($request->except(self::redactInputKeys())), []));
        }

        if ($request->hasFile('proof_image') || $request->hasFile('images')) {
            $files = [];
            foreach ((array) $request->file('images', []) as $f) {
                if ($f) {
                    $files[] = AuditLogger::fileMeta($f);
                }
            }
            if ($request->hasFile('proof_image')) {
                $files[] = AuditLogger::fileMeta($request->file('proof_image'));
            }
            if ($files !== []) {
                $props['files'] = $files;
            }
        }

        return array_filter($props, fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /** @return array<int, string> */
    protected static function redactInputKeys(): array
    {
        return ['password', 'password_confirmation', '_token', 'token', 'proof_image', 'images'];
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
