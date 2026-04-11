<?php

namespace App\Models;

use App\Enums\GameSystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Reference data model for monsters, populated from Open5e API
 *
 * @description All data is stored in dedicated columns. Translatable fields
 * use spatie/laravel-translatable with JSON columns.
 *
 * @example
 * Monster::forGameSystem(GameSystem::Dnd5e2024)->where('type', 'Dragon')->get();
 * Monster::whereTranslation('name', '%Goblin%')->orderByTranslation('name')->get();
 * $monster->abilityModifier('strength');
 */
class Monster extends Model
{
    use Concerns\HasAbilityScores;
    use Concerns\HasTranslatableScopes;
    use HasFactory;
    use HasTranslations;

    public array $translatable = [
        'name',
        'desc',
        'traits',
        'actions',
        'legendary_actions',
        'reactions',
        'bonus_actions',
        'special_abilities',
    ];

    protected $fillable = [
        'slug',
        'name',
        'size',
        'type',
        'subtype',
        'alignment',
        'desc',
        'challenge_rating',
        'cr',
        'armor_class',
        'hit_points',
        'hit_dice',
        'traits',
        'actions',
        'legendary_actions',
        'reactions',
        'bonus_actions',
        'special_abilities',
        'speed',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'saving_throws',
        'armor_detail',
        'document_slug',
        'document_title',
        'img_url',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'speed' => 'array',
            'saving_throws' => 'array',
            'cr' => 'decimal:2',
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

    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class)->withTimestamps();
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeForGameSystem(Builder $query, GameSystem $system): Builder
    {
        return $query->whereIn('document_slug', $system->documentSlugs());
    }
}
