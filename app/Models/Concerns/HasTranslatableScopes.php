<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Query scopes for searching and ordering Spatie translatable JSON columns
 *
 * @description Provides locale-aware LIKE search and ORDER BY on JSON columns
 * managed by spatie/laravel-translatable. Uses MySQL JSON_EXTRACT functions.
 *
 * @example
 * Monster::whereTranslation('name', '%Goblin%')->orderByTranslation('name')->get();
 * Spell::whereTranslation('name', '%Fire%')->orderByTranslation('name', 'desc')->get();
 */
trait HasTranslatableScopes
{
    /**
     * Filter by a translatable column value using the current locale
     */
    public function scopeWhereTranslation(Builder $query, string $column, string $value): Builder
    {
        $locale = app()->getLocale();

        return $query->whereRaw(
            "JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, ?)) LIKE ?",
            ['$."' . $locale . '"', $value]
        );
    }

    /**
     * Order by a translatable column using the current locale
     */
    public function scopeOrderByTranslation(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        $locale = app()->getLocale();
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return $query->orderByRaw(
            "JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, ?)) {$direction}",
            ['$."' . $locale . '"']
        );
    }
}
