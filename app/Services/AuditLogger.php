<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class AuditLogger
{
    /** @var array<int, string> */
    private const REDACT_KEYS = [
        'password', 'password_confirmation', 'current_password', 'new_password',
        '_token', 'csrf_token', 'token', 'api_token', 'access_token', 'refresh_token',
        'secret', 'client_secret', 'server_key', 'signature_key', 'signature',
        'authorization', 'cookie', 'cookies', 'session', 'remember_token',
    ];

    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, mixed>|UploadedFile|null  $file
     */
    public static function log(
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?Request $request = null,
        UploadedFile|array|null $file = null,
        ?string $subjectLabel = null,
    ): ?ActivityLog {
        try {
            $request ??= request();
            $user = $request?->user();
            $fileMeta = self::fileMeta($file);

            $data = [
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'event' => $event,
                'description' => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subjectLabel ?? self::subjectLabel($subject),
                'request_id' => $request?->attributes->get('audit_request_id'),
                'ip' => (string) ($request?->ip() ?? ''),
                'method' => $request?->method() ?? 'SYS',
                'path' => Str::limit($request?->path() ?? '', 500),
                'route' => $request?->route()?->getName(),
                'status' => 200,
                'properties' => self::sanitize($properties),
                'file_name' => $fileMeta['file_name'] ?? null,
                'file_mime' => $fileMeta['file_mime'] ?? null,
                'file_size' => $fileMeta['file_size'] ?? null,
                'file_path' => $fileMeta['file_path'] ?? null,
                'file_hash' => $fileMeta['file_hash'] ?? null,
                'user_agent' => $request?->userAgent(),
            ];

            return ActivityLog::create($data);
        } catch (Throwable $e) {
            try {
                \Log::warning('Audit log gagal', ['event' => $event, 'error' => $e->getMessage()]);
            } catch (Throwable) {
            }

            return null;
        }
    }

    public static function subjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }
        foreach (['order_code', 'name', 'email', 'title', 'code'] as $key) {
            $val = $subject->getAttribute($key);
            if (is_string($val) && $val !== '') {
                return Str::limit($val, 120);
            }
        }

        return class_basename($subject).'#'.$subject->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 6) {
            return '[truncated]';
        }
        if ($value instanceof UploadedFile) {
            return self::fileMeta($value);
        }
        if ($value instanceof Model) {
            return ['id' => $value->getKey(), 'type' => class_basename($value)];
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $key = is_string($k) ? strtolower($k) : $k;
                if (is_string($key) && self::isRedactedKey($key)) {
                    $out[$k] = '[REDACTED]';

                    continue;
                }
                $out[$k] = self::sanitize($v, $depth + 1);
            }

            return $out;
        }
        if (is_string($value) && strlen($value) > 2000) {
            return Str::limit($value, 2000);
        }

        return $value;
    }

    private static function isRedactedKey(string $key): bool
    {
        foreach (self::REDACT_KEYS as $needle) {
            if ($key === $needle || str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|UploadedFile|null  $file
     * @return array<string, mixed>
     */
    public static function fileMeta(UploadedFile|array|null $file): array
    {
        if ($file === null) {
            return [];
        }
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();

            return array_filter([
                'file_name' => $file->getClientOriginalName(),
                'file_mime' => $file->getMimeType() ?: $file->getClientMimeType(),
                'file_size' => $file->getSize() ?: null,
                'file_hash' => ($path && is_file($path)) ? hash_file('sha256', $path) : null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return Arr::only($file, ['file_name', 'file_mime', 'file_size', 'file_path', 'file_hash']);
    }
}
