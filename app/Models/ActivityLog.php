<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'user_name', 'user_role', 'event', 'description',
        'subject_type', 'subject_id', 'subject_label', 'request_id',
        'ip', 'method', 'path', 'route', 'status', 'properties',
        'file_name', 'file_mime', 'file_size', 'file_path', 'file_hash',
        'user_agent',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'subject_id' => 'integer',
        'status' => 'integer',
        'file_size' => 'integer',
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function pruneOlderThan(int $days): int
    {
        return static::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * @return array<int, int>
     */
    public static function retentionOptions(): array
    {
        return [0, 3, 7, 30, 365];
    }
}
