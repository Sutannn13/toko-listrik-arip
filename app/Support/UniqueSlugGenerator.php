<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlugGenerator
{
    public static function make(string $modelClass, string $value, string $column = 'slug', ?int $ignoreId = null): string
    {
        /** @var Model $model */
        $model = new $modelClass();
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'item';
        }

        // Keep space for -999 suffix in edge cases.
        $baseSlug = Str::limit($baseSlug, 240, '');

        $slug = $baseSlug;
        $suffix = 2;

        while (self::exists($modelClass, $column, $slug, $model->getKeyName(), $ignoreId)) {
            $nextSuffix = '-' . $suffix;
            $slug = Str::limit($baseSlug, 240 - strlen($nextSuffix), '') . $nextSuffix;
            $suffix++;
        }

        return $slug;
    }

    private static function exists(
        string $modelClass,
        string $column,
        string $slug,
        string $keyName,
        ?int $ignoreId
    ): bool {
        $query = $modelClass::query()->where($column, $slug);

        if ($ignoreId !== null) {
            $query->where($keyName, '!=', $ignoreId);
        }

        return $query->exists();
    }
}
