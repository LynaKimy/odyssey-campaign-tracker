<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Player character (PC) linked to a user and a campaign
 *
 * @description Ability scores are stored as columns (universal to D&D-like
 * systems). System-specific data goes in extra_data JSON.
 *
 * @example
 * $character->abilityModifier('strength'); // (score - 10) / 2
 * $character->preparedSpells()->get();
 */
class Character extends Model
{
    use Concerns\HasAbilityScores;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'name',
        'race',
        'class',
        'level',
        'max_hp',
        'current_hp',
        'armor_class',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'extra_data',
        'backstory',
        'avatar',
    ];

    protected function casts(): array
    {
        return [
            'extra_data' => 'array',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class)
            ->withPivot('is_prepared')
            ->withTimestamps();
    }

    public function preparedSpells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class)
            ->withPivot('is_prepared')
            ->wherePivot('is_prepared', true)
            ->withTimestamps();
    }

}
