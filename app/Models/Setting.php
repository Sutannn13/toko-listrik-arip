<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'group', 'type', 'label'];

    /**
     * Get a setting value by key, with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!Schema::hasTable((new static())->getTable())) {
            return $default;
        }

        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->type === 'boolean') {
            $normalized = filter_var((string) $setting->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $normalized ?? false;
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        if (!Schema::hasTable((new static())->getTable())) {
            return;
        }

        static::where('key', $key)->update(['value' => $value]);
    }

    /**
     * Get all settings in a group as a key=>value array.
     */
    public static function getGroup(string $group): array
    {
        if (!Schema::hasTable((new static())->getTable())) {
            return [];
        }

        return static::where('group', $group)
            ->get()
            ->keyBy('key')
            ->toArray();
    }
}
