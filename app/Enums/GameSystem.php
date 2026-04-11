<?php

namespace App\Enums;

/**
 * Supported tabletop RPG game systems
 *
 * @description Backed string enum for game systems. Each case maps
 * to one or more Open5e document slugs for filtering reference data.
 *
 * @example
 * $campaign->system === GameSystem::Dnd5e2024;
 * Monster::forGameSystem(GameSystem::Dnd5e2024)->get();
 */
enum GameSystem: string
{
    case Dnd5e2024 = 'dnd5e-2024';
    case Dnd5e2014 = 'dnd5e-2014';

    public function label(): string
    {
        return __("enums.game_system.{$this->value}");
    }

    /**
     * Open5e document slugs associated with this game system
     *
     * @return list<string>
     */
    public function documentSlugs(): array
    {
        return match ($this) {
            self::Dnd5e2024 => ['srd-2024', 'open5e-2024'],
            self::Dnd5e2014 => ['srd-2014', 'tob', 'tob-2023', 'tob2', 'tob3', 'ccdx', 'deepm', 'vom'],
        };
    }
}
