<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Read a setting value (string), with a fallback default.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Persist a setting value (overwrite or insert).
     */
    public static function set(string $key, ?string $value): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function maintenanceEnabled(): bool
    {
        return static::get('maintenance_mode', '0') === '1';
    }

    public static function maintenanceMessage(): string
    {
        return static::get('maintenance_message', 'SIRESTEDUH sedang dalam perbaikan. Kami akan segera kembali. Terima kasih atas pengertian Anda.');
    }
}
