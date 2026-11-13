<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'user_name', 'user_role', 'ip', 'method', 'path', 'route', 'status', 'user_agent',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Delete activity rows older than the given number of days.
     */
    public static function pruneOlderThan(int $days): int
    {
        return static::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * Retention options in days, shown in the admin UI.
     *
     * @return array<int, int>
     */
    public static function retentionOptions(): array
    {
        return [0, 3, 7, 30, 365];
    }
}
