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
     * Filter by a translatable column value using the current locale with fallback
     */
    public function scopeWhereTranslation(Builder $query, string $column, string $value): Builder
    {
        $locale = app()->getLocale();
        $fallback = $this->getFallbackLocale();

        return $query->whereRaw(
            "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, ?)), JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, ?)))) LIKE LOWER(?)",
            ['$."' . $locale . '"', '$."' . $fallback . '"', $value]
        );
    }

    /**
     * Order by a translatable column using the current locale with fallback
     */
    public function scopeOrderByTranslation(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        $locale = app()->getLocale();
        $fallback = $this->getFallbackLocale();
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return $query->orderByRaw(
            "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, ?)), JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, ?))) {$direction}",
            ['$."' . $locale . '"', '$."' . $fallback . '"']
        );
    }
}
