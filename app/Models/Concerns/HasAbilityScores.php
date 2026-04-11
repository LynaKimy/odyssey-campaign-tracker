<?php

namespace App\Models\Concerns;

/**
 * Shared ability score logic for Character and Monster models
 *
 * @description Provides the D&D ability modifier calculation and a constant
 * listing all six ability score column names for iteration in views.
 *
 * @example
 * $character->abilityModifier('strength'); // 10 → 0, 16 → +3, 8 → -1
 * foreach (Character::ABILITIES as $ability) { ... }
 */
trait HasAbilityScores
{
    public const ABILITIES = [
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    /**
     * Calculate ability score modifier: (score - 10) / 2, rounded down
     */
    public function abilityModifier(string $ability): int
    {
        $score = $this->{$ability};

        return $score !== null ? intdiv($score - 10, 2) : 0;
    }
}
