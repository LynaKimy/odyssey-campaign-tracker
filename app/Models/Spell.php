<?php

namespace App\Models;

use App\Enums\GameSystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * Reference data model for spells, populated from Open5e API
 *
 * @description Core fields stored as columns for filtering.
 * Translatable fields use spatie/laravel-translatable.
 *
 * @example
 * Spell::forGameSystem(GameSystem::Dnd5e2024)->cantrips()->get();
 * Spell::whereTranslation('name', '%Fire%')->orderByTranslation('name')->get();
 */
class Spell extends Model
{
    use Concerns\HasTranslatableScopes;
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'desc', 'higher_level'];

    protected $fillable = [
        'slug',
        'name',
        'level_int',
        'school',
        'casting_time',
        'range',
        'duration',
        'requires_concentration',
        'can_be_cast_as_ritual',
        'components',
        'desc',
        'higher_level',
        'dnd_class',
        'document_slug',
        'document_title',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_concentration' => 'boolean',
            'can_be_cast_as_ritual' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function getFallbackLocale(): string
    {
        return 'en';
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class)
            ->withPivot('is_prepared')
            ->withTimestamps();
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeForGameSystem(Builder $query, GameSystem $system): Builder
    {
        return $query->whereIn('document_slug', $system->documentSlugs());
    }

    public function scopeCantrips(Builder $query): Builder
    {
        return $query->where('level_int', 0);
    }

    public function scopeOfLevel(Builder $query, int $level): Builder
    {
        return $query->where('level_int', $level);
    }
}
